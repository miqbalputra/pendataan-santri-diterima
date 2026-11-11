from __future__ import annotations

import asyncio
import hmac
import logging
import os
from contextlib import asynccontextmanager

from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.responses import JSONResponse
from paddleocr import PaddleOCR

from document_parser import enhanced_variant, load_image, merge_candidates, parse_document, result_strength

MAX_IMAGE_BYTES = 10 * 1024 * 1024
OCR_TOKEN = os.environ.get("OCR_SERVICE_TOKEN", "")
AUTO_FILL_ENABLED = os.environ.get("OCR_AUTO_FILL_ENABLED", "false").lower() == "true"
OCR_SEMAPHORE = asyncio.Semaphore(1)
ocr_engine: PaddleOCR | None = None
logger = logging.getLogger("spsb.ocr")


@asynccontextmanager
async def lifespan(_: FastAPI):
    global ocr_engine
    if not OCR_TOKEN:
        raise RuntimeError("OCR_SERVICE_TOKEN must be configured")
    import paddle

    paddle.set_device("cpu")
    paddle.set_num_threads(2)
    ocr_engine = PaddleOCR(
        lang="id",
        ocr_version="PP-OCRv6",
        device="cpu",
        use_doc_orientation_classify=False,
        use_doc_unwarping=False,
        use_textline_orientation=False,
    )
    yield
    ocr_engine = None


app = FastAPI(title="Private SPSB OCR", docs_url=None, redoc_url=None, openapi_url=None, lifespan=lifespan)


@app.get("/healthz")
async def healthz():
    return {"status": "ok", "model_loaded": ocr_engine is not None}


def _infer(image, target: str, quality_warnings: list[str], quality_score: float) -> dict:
    primary = parse_document(
        ocr_engine.predict(image), target,
        auto_fill_enabled=AUTO_FILL_ENABLED,
        quality_score=quality_score,
        quality_warnings=quality_warnings,
    )
    if result_strength(primary)[0] < 2 or not primary.get("document_check", {}).get("is_expected_document"):
        alternate = parse_document(
            ocr_engine.predict(enhanced_variant(image)), target,
            auto_fill_enabled=AUTO_FILL_ENABLED,
            quality_score=quality_score,
            quality_warnings=quality_warnings,
        )
        return merge_candidates(primary, alternate)
    return primary


@app.post("/internal/ocr")
async def recognize(file: UploadFile = File(...), target: str = Form(...)):
    if target not in {"akta", "kk", "ayah", "ibu"}:
        raise HTTPException(status_code=422, detail="Unsupported OCR target")
    if ocr_engine is None:
        raise HTTPException(status_code=503, detail="OCR model is not ready")

    raw = await file.read(MAX_IMAGE_BYTES + 1)
    if len(raw) > MAX_IMAGE_BYTES:
        raise HTTPException(status_code=413, detail="Image exceeds the 10 MB limit")
    if not raw:
        raise HTTPException(status_code=422, detail="Image is empty")

    try:
        async with OCR_SEMAPHORE:
            image, quality_warnings, quality_score = await asyncio.to_thread(load_image, raw, target)
            parsed = await asyncio.to_thread(_infer, image, target, quality_warnings, quality_score)
    except HTTPException:
        raise
    except Exception as exc:
        # Do not log image bytes, recognized text, or provider payloads.
        logger.warning("OCR inference failed (%s)", type(exc).__name__)
        raise HTTPException(status_code=503, detail="OCR processing failed") from None
    finally:
        raw = b""

    return JSONResponse(parsed)


@app.middleware("http")
async def require_service_token(request, call_next):
    if request.url.path == "/healthz":
        return await call_next(request)
    supplied = request.headers.get("authorization", "")
    expected = f"Bearer {OCR_TOKEN}"
    if not OCR_TOKEN or not hmac.compare_digest(supplied.encode("utf-8"), expected.encode("utf-8")):
        return JSONResponse(status_code=404, content={"detail": "Not found"})
    return await call_next(request)

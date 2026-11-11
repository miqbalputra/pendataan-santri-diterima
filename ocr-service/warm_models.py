"""Resolve and warm the official OCR models during image build, not per request."""

import numpy as np
from paddleocr import PaddleOCR

engine = PaddleOCR(
    lang="id",
    ocr_version="PP-OCRv6",
    device="cpu",
    use_doc_orientation_classify=False,
    use_doc_unwarping=False,
    use_textline_orientation=False,
)
engine.predict(np.full((640, 960, 3), 255, dtype=np.uint8))
print("PP-OCRv6 Indonesian model assets are cached in the image.")

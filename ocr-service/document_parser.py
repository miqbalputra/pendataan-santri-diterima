from __future__ import annotations

import re
import json
from datetime import datetime
from statistics import median
from typing import Any

import cv2
import numpy as np
from PIL import Image, ImageOps

MAX_PIXELS = 40_000_000
AUTO_THRESHOLDS = {"nik": 0.985, "nama": 0.95, "tanggal_lahir": 0.95, "alamat": 0.95}
FIELD_TITLES = {
    "nik": "NIK", "nama": "nama", "tempat_lahir": "tempat lahir",
    "tanggal_lahir": "tanggal lahir", "jenis_kelamin": "jenis kelamin",
    "alamat": "alamat", "rt_rw": "RT/RW", "kelurahan_desa": "kelurahan/desa",
    "kecamatan": "kecamatan", "pekerjaan": "pekerjaan",
}


def load_image(raw: bytes, target: str) -> tuple[np.ndarray, list[str], float]:
    """Decode EXIF orientation, assess image quality and correct a clear document quadrilateral."""
    import io

    with Image.open(io.BytesIO(raw)) as source:
        image = ImageOps.exif_transpose(source).convert("RGB")
        width, height = image.size
        if width * height > MAX_PIXELS:
            raise ValueError("Image has too many pixels")
        rgb = np.asarray(image)
    bgr = cv2.cvtColor(rgb, cv2.COLOR_RGB2BGR)

    if target in {"ayah", "ibu", "kk"} and bgr.shape[0] > bgr.shape[1] * 1.05:
        bgr = cv2.rotate(bgr, cv2.ROTATE_90_CLOCKWISE)
    elif target == "akta" and bgr.shape[1] > bgr.shape[0] * 1.2:
        bgr = cv2.rotate(bgr, cv2.ROTATE_90_CLOCKWISE)
    if max(bgr.shape[:2]) > 4096:
        scale = 4096 / max(bgr.shape[:2])
        bgr = cv2.resize(bgr, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)

    warnings: list[str] = []
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
    small_scale = min(1.0, 1200 / max(gray.shape))
    small = cv2.resize(gray, None, fx=small_scale, fy=small_scale, interpolation=cv2.INTER_AREA) if small_scale < 1 else gray
    brightness = float(np.mean(small))
    blur_score = float(cv2.Laplacian(small, cv2.CV_64F).var())
    quality_score = 1.0
    if brightness < 58:
        warnings.append("Foto terlihat terlalu gelap.")
        quality_score -= 0.25
    elif brightness > 248:
        warnings.append("Foto terlihat terlalu terang.")
        quality_score -= 0.2
    if blur_score < 48:
        warnings.append("Tulisan mungkin buram atau kurang fokus.")
        quality_score -= 0.35

    corrected = _perspective_correct(bgr, target)
    if corrected is not None:
        bgr = corrected
    else:
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
        bgr = cv2.cvtColor(clahe.apply(cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)), cv2.COLOR_GRAY2BGR)

    return bgr, warnings, max(0.0, quality_score)


def enhanced_variant(image: np.ndarray) -> np.ndarray:
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    gray = cv2.createCLAHE(clipLimit=2.2, tileGridSize=(8, 8)).apply(gray)
    return cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 31, 11)


def parse_document(result: Any, target: str, auto_fill_enabled: bool = False, quality_score: float = 1.0, quality_warnings: list[str] | None = None) -> dict[str, Any]:
    lines = _result_lines(result)
    text = "\n".join(item["text"] for item in lines).upper()
    document_type, expected, document_confidence = _classify(text, target)
    warnings = list(quality_warnings or [])

    gender = _extract_gender(text)
    expected_gender = {"ayah": "Laki-laki", "ibu": "Perempuan"}.get(target)
    gender_matches = not expected_gender or not gender or expected_gender == gender
    if expected and not gender_matches:
        expected = False
        warnings.append("Jenis kelamin pada foto tidak sesuai dengan dokumen yang dipilih.")
    if not expected:
        warnings.append("Jenis dokumen belum dapat dipastikan. Periksa foto atau isi data manual.")
        return {
            "document_type": document_type,
            "document_check": {
                "expected_type": target, "document_type": document_type,
                "confidence": document_confidence, "is_expected_document": False,
                "expected_gender": expected_gender, "detected_gender": gender or None,
                "gender_matches": gender_matches,
            },
            "fields": {}, "warnings": _unique(warnings),
        }

    extracted = _extract_fields(lines, target)
    fields: dict[str, dict[str, Any]] = {}
    for key, (value, confidence) in extracted.items():
        if not value:
            continue
        confidence = min(1.0, max(0.0, confidence * quality_score))
        if confidence >= 0.65:
            auto_gate = auto_fill_enabled and confidence >= AUTO_THRESHOLDS.get(key, 0.95)
            decision = "auto" if auto_gate else "confirm"
            fields[key] = {"value": value, "confidence": round(confidence, 3), "decision": decision}
        else:
            fields[key] = {"value": "", "confidence": round(confidence, 3), "decision": "manual"}

    if not fields:
        warnings.append("Tidak ada kolom yang cukup jelas untuk disarankan. Isi data secara manual.")
    elif quality_score < 0.7:
        warnings.append("Kualitas foto rendah. Cocokkan setiap saran dengan dokumen asli.")

    return {
        "document_type": document_type,
        "document_check": {
            "expected_type": target, "document_type": document_type,
            "confidence": "low" if quality_score < 0.45 else document_confidence,
            "is_expected_document": True,
            "expected_gender": expected_gender, "detected_gender": gender or None,
            "gender_matches": gender_matches,
        },
        "fields": fields, "warnings": _unique(warnings),
    }


def merge_candidates(first: dict[str, Any], second: dict[str, Any]) -> dict[str, Any]:
    """Prefer clearer fields; remove close conflicts so the UI cannot suggest a guessed value."""
    if first.get("document_check", {}).get("is_expected_document") is False and second.get("document_check", {}).get("is_expected_document") is True:
        merged = {**second, "fields": dict(second.get("fields", {}))}
        merged["warnings"] = _unique(first.get("warnings", []) + second.get("warnings", []))
        return merged
    if second.get("document_check", {}).get("is_expected_document") is False:
        second = {**second, "fields": {}}
    merged = {**first, "fields": dict(first.get("fields", {}))}
    for key, candidate in second.get("fields", {}).items():
        current = merged["fields"].get(key)
        if not current:
            merged["fields"][key] = candidate
            continue
        if current.get("value") == candidate.get("value"):
            if float(candidate.get("confidence", 0)) > float(current.get("confidence", 0)):
                merged["fields"][key] = candidate
            continue
        gap = abs(float(current.get("confidence", 0)) - float(candidate.get("confidence", 0)))
        if gap < 0.12:
            merged["fields"][key] = {
                "value": "", "confidence": max(current.get("confidence", 0), candidate.get("confidence", 0)),
                "decision": "manual",
            }
            merged.setdefault("warnings", []).append(f"Hasil baca kolom {FIELD_TITLES.get(key, key)} berbeda. Periksa dokumen dan isi manual.")
        elif float(candidate.get("confidence", 0)) > float(current.get("confidence", 0)):
            merged["fields"][key] = candidate
    merged["warnings"] = _unique(merged.get("warnings", []) + second.get("warnings", []))
    return merged


def result_strength(result: dict[str, Any]) -> tuple[int, float]:
    fields = [item for item in result.get("fields", {}).values() if item.get("value")]
    return len(fields), sum(float(item.get("confidence", 0)) for item in fields)


def _perspective_correct(image: np.ndarray, target: str) -> np.ndarray | None:
    height, width = image.shape[:2]
    scale = min(1.0, 1200 / max(width, height))
    source = cv2.resize(image, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA) if scale < 1 else image
    gray = cv2.cvtColor(source, cv2.COLOR_BGR2GRAY)
    edges = cv2.Canny(cv2.GaussianBlur(gray, (5, 5), 0), 45, 130)
    contours, _ = cv2.findContours(cv2.dilate(edges, np.ones((3, 3), np.uint8)), cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
    image_area = source.shape[0] * source.shape[1]
    candidates = []
    for contour in sorted(contours, key=cv2.contourArea, reverse=True)[:30]:
        area = cv2.contourArea(contour)
        if area < image_area * 0.18 or area > image_area * 0.985:
            continue
        perimeter = cv2.arcLength(contour, True)
        polygon = cv2.approxPolyDP(contour, 0.02 * perimeter, True)
        if len(polygon) != 4 or not cv2.isContourConvex(polygon):
            continue
        points = _order_points(polygon.reshape(4, 2).astype("float32"))
        top = np.linalg.norm(points[1] - points[0])
        bottom = np.linalg.norm(points[2] - points[3])
        left = np.linalg.norm(points[3] - points[0])
        right = np.linalg.norm(points[2] - points[1])
        ratio = max(top, bottom) / max(1.0, max(left, right))
        if target in {"ayah", "ibu"} and not 1.30 <= ratio <= 1.90:
            continue
        if target == "kk" and not 1.15 <= ratio <= 1.7:
            continue
        if target == "akta" and not 0.5 <= ratio <= 1.0:
            continue
        candidates.append((area, points))
    if not candidates:
        return None

    _, points = max(candidates, key=lambda item: item[0])
    points /= scale
    top_left, top_right, bottom_right, bottom_left = points
    out_width = int(max(np.linalg.norm(bottom_right - bottom_left), np.linalg.norm(top_right - top_left)))
    out_height = int(max(np.linalg.norm(top_right - bottom_right), np.linalg.norm(top_left - bottom_left)))
    if out_width < 300 or out_height < 180:
        return None
    destination = np.array([[0, 0], [out_width - 1, 0], [out_width - 1, out_height - 1], [0, out_height - 1]], dtype="float32")
    return cv2.warpPerspective(image, cv2.getPerspectiveTransform(points, destination), (out_width, out_height), flags=cv2.INTER_CUBIC, borderMode=cv2.BORDER_REPLICATE)


def _order_points(points: np.ndarray) -> np.ndarray:
    result = np.zeros((4, 2), dtype="float32")
    sums, diffs = points.sum(axis=1), np.diff(points, axis=1).ravel()
    result[0], result[2] = points[np.argmin(sums)], points[np.argmax(sums)]
    result[1], result[3] = points[np.argmin(diffs)], points[np.argmax(diffs)]
    return result


def _result_lines(result: Any) -> list[dict[str, Any]]:
    if result is None:
        return []
    if isinstance(result, dict) or hasattr(result, "json"):
        results = [result]
    else:
        try:
            results = list(result)
        except TypeError:
            results = [result]
    lines: list[dict[str, Any]] = []
    for item in results:
        if hasattr(item, "json"):
            data = item.json
            if callable(data):
                data = data()
        elif isinstance(item, dict):
            data = item
        else:
            continue
        if isinstance(data, str):
            try:
                data = json.loads(data)
            except json.JSONDecodeError:
                continue
        if not isinstance(data, dict):
            continue
        inner = data.get("res", data)
        texts = inner.get("rec_texts", inner.get("texts", [])) or []
        scores = inner.get("rec_scores", inner.get("scores", [])) or []
        boxes = inner.get("rec_polys", inner.get("dt_polys", inner.get("boxes", []))) or []
        for index, value in enumerate(texts):
            text = re.sub(r"\s+", " ", str(value)).strip()
            if not text:
                continue
            score = float(scores[index]) if index < len(scores) else 0.5
            box = np.asarray(boxes[index], dtype=float) if index < len(boxes) else np.zeros((4, 2))
            if box.ndim == 2 and box.shape[0] >= 2:
                xs, ys = box[:, 0], box[:, 1]
                x, y = float(np.mean(xs)), float(np.mean(ys))
                height = float(max(ys) - min(ys))
            else:
                x, y, height = float(index), float(index * 30), 20.0
            lines.append({"text": text, "score": max(0.0, min(1.0, score)), "x": x, "y": y, "height": height})
    return _group_lines(lines)


def _group_lines(items: list[dict[str, Any]]) -> list[dict[str, Any]]:
    if not items:
        return []
    items.sort(key=lambda item: (item["y"], item["x"]))
    threshold = max(8.0, median(item["height"] for item in items) * 0.55)
    groups: list[list[dict[str, Any]]] = []
    for item in items:
        if not groups or abs(item["y"] - np.mean([part["y"] for part in groups[-1]])) > threshold:
            groups.append([item])
        else:
            groups[-1].append(item)
    result = []
    for group in groups:
        group.sort(key=lambda item: item["x"])
        result.append({
            "text": " ".join(item["text"] for item in group),
            "score": sum(item["score"] for item in group) / len(group),
        })
    return result


def _classify(text: str, target: str) -> tuple[str, bool, str]:
    if target in {"ayah", "ibu"}:
        markers = sum(bool(re.search(marker, text)) for marker in [r"\bNIK\b", r"\bNAMA\b", r"\bALAMAT\b", r"\bPROVINSI\b", r"\bKECAMATAN\b", r"\bKEL\b|\bDESA\b"])
        expected = markers >= 3 and bool(re.search(r"\bNIK\b", text))
        return ("ktp" if expected else "unknown"), expected, ("high" if markers >= 4 else "medium" if expected else "low")
    if target == "kk":
        markers = sum(bool(re.search(marker, text)) for marker in [r"KARTU KELUARGA", r"NOMOR KARTU KELUARGA", r"KEPALA KELUARGA", r"\bALAMAT\b", r"\bNIK\b"])
        expected = markers >= 2 and bool(re.search(r"KARTU KELUARGA|NOMOR KARTU KELUARGA", text))
        return ("kk" if expected else "unknown"), expected, ("high" if markers >= 3 else "medium" if expected else "low")
    markers = sum(bool(re.search(marker, text)) for marker in [r"AKTA KELAHIRAN", r"KUTIPAN AKTA", r"PENCATATAN KELAHIRAN", r"\bNAMA\b", r"\bLAHIR\b"])
    expected = markers >= 2 and bool(re.search(r"AKTA KELAHIRAN|KUTIPAN AKTA|PENCATATAN KELAHIRAN", text))
    return ("akta" if expected else "unknown"), expected, ("high" if markers >= 3 else "medium" if expected else "low")


def _extract_fields(lines: list[dict[str, Any]], target: str) -> dict[str, tuple[str, float]]:
    text = "\n".join(line["text"].upper() for line in lines)
    fields: dict[str, tuple[str, float]] = {}

    if target in {"ayah", "ibu", "akta"}:
        nik = _extract_nik(lines)
        if nik:
            fields["nik"] = nik
        name = _labeled_value(lines, r"\bNAMA\b|\bBERNAMA\b")
        if name and not _contains_label(name[0]):
            fields["nama"] = name
        birth = _find_line(lines, r"TEMPAT\s*/?\s*TGL\s*LAHIR|TEMPAT\s+TANGGAL\s+LAHIR|\bLAHIR\b")
        if birth:
            birth_text = re.sub(r"^.*?(?:TEMPAT\s*/?\s*TGL\s*LAHIR|TEMPAT\s+TANGGAL\s+LAHIR|\bLAHIR\b)\s*[:;.-]?", "", birth[0], flags=re.I)
            match = re.search(r"([A-Z][A-Z .'-]{1,40})[,:; ]+(.+)$", birth_text)
            date = _parse_date(birth[0])
            if match:
                place = _clean_value(match.group(1))
                if place and not _contains_label(place):
                    fields["tempat_lahir"] = (place.title(), birth[1])
            if date:
                fields["tanggal_lahir"] = (date, birth[1])
        if target in {"ayah", "ibu"}:
            address = _labeled_value(lines, r"\bALAMAT\b")
            if address and not _contains_label(address[0]):
                fields["alamat"] = address
            rt_rw = _regex_field(lines, r"\bRT\s*/?\s*RW\b", r"(\d{1,3})\s*[/\\-]\s*(\d{1,3})")
            if rt_rw:
                fields["rt_rw"] = (f"{int(rt_rw[0]):03d}/{int(rt_rw[1]):03d}", rt_rw[2])
            village = _labeled_value(lines, r"KEL\s*/?\s*DESA|KELURAHAN|\bDESA\b")
            if village and not _contains_label(village[0]):
                fields["kelurahan_desa"] = village
            district = _labeled_value(lines, r"\bKECAMATAN\b")
            if district and not _contains_label(district[0]):
                fields["kecamatan"] = district
            job = _labeled_value(lines, r"\bPEKERJAAN\b")
            if job and not _contains_label(job[0]):
                fields["pekerjaan"] = job
            gender = _extract_gender(text)
            if gender:
                fields["jenis_kelamin"] = (gender, _confidence_for(lines, r"LAKI.?LAKI|PEREMPUAN|WANITA|PRIA"))
        elif target == "akta":
            gender = _extract_gender(text)
            if gender:
                fields["jenis_kelamin"] = (gender, _confidence_for(lines, r"LAKI.?LAKI|PEREMPUAN|WANITA|PRIA"))
        return fields

    if target == "kk":
        address = _labeled_value(lines, r"\bALAMAT\b")
        if address and not _contains_label(address[0]):
            fields["alamat"] = address
        rt_rw = _regex_field(lines, r"\bRT\s*/?\s*RW\b", r"(\d{1,3})\s*[/\\-]\s*(\d{1,3})")
        if rt_rw:
            fields["rt_rw"] = (f"{int(rt_rw[0]):03d}/{int(rt_rw[1]):03d}", rt_rw[2])
        village = _labeled_value(lines, r"KEL\s*/?\s*DESA|KELURAHAN|\bDESA\b")
        if village and not _contains_label(village[0]):
            fields["kelurahan_desa"] = village
        district = _labeled_value(lines, r"\bKECAMATAN\b")
        if district and not _contains_label(district[0]):
            fields["kecamatan"] = district
    return fields


def _extract_nik(lines: list[dict[str, Any]]) -> tuple[str, float] | None:
    for index, line in enumerate(lines):
        if not re.search(r"\bNIK\b|NOMOR INDUK KEPENDUDUKAN", line["text"], re.I):
            continue
        candidates = re.findall(r"(?<!\d)(\d[\d ]{14,20}\d)(?!\d)", line["text"])
        score = line["score"]
        if not candidates and index + 1 < len(lines):
            candidates = re.findall(r"(?<!\d)(\d[\d ]{14,20}\d)(?!\d)", lines[index + 1]["text"])
            score = (score + lines[index + 1]["score"]) / 2
        for candidate in candidates:
            digits = re.sub(r"\D", "", candidate)
            if len(digits) == 16:
                return digits, score
    return None


def _labeled_value(lines: list[dict[str, Any]], label: str) -> tuple[str, float] | None:
    for index, line in enumerate(lines):
        match = re.search(rf"(?:^|\b)(?:{label})\b\s*[:;.-]?\s*(.*)$", line["text"], re.I)
        if not match:
            continue
        value = _clean_value(match.group(1))
        score = line["score"]
        if not value and index + 1 < len(lines):
            value = _clean_value(lines[index + 1]["text"])
            score = (score + lines[index + 1]["score"]) / 2
        return (value, score) if value else None
    return None


def _find_line(lines: list[dict[str, Any]], pattern: str) -> tuple[str, float] | None:
    return next(((line["text"], line["score"]) for line in lines if re.search(pattern, line["text"], re.I)), None)


def _regex_field(lines: list[dict[str, Any]], label: str, value_pattern: str):
    for line in lines:
        if re.search(label, line["text"], re.I):
            match = re.search(value_pattern, line["text"])
            if match:
                return (*match.groups(), line["score"])
    return None


def _confidence_for(lines: list[dict[str, Any]], pattern: str) -> float:
    return next((line["score"] for line in lines if re.search(pattern, line["text"], re.I)), 0.0)


def _extract_gender(text: str) -> str:
    if re.search(r"\b(PEREMPUAN|WANITA)\b", text):
        return "Perempuan"
    if re.search(r"\b(LAKI.?LAKI|PRIA)\b", text):
        return "Laki-laki"
    return ""


def _parse_date(text: str) -> str | None:
    value = text.upper()
    patterns = [r"(?<!\d)(\d{1,2})[./ -](\d{1,2})[./ -](\d{4})(?!\d)", r"(?<!\d)(\d{4})[./ -](\d{1,2})[./ -](\d{1,2})(?!\d)"]
    for index, pattern in enumerate(patterns):
        match = re.search(pattern, value)
        if not match:
            continue
        day, month, year = (match.group(1), match.group(2), match.group(3)) if index == 0 else (match.group(3), match.group(2), match.group(1))
        try:
            return datetime(int(year), int(month), int(day)).strftime("%Y-%m-%d")
        except ValueError:
            return None
    months = {"JANUARI": 1, "JAN": 1, "FEBRUARI": 2, "FEB": 2, "MARET": 3, "MAR": 3, "APRIL": 4, "APR": 4, "MEI": 5, "JUNI": 6, "JUN": 6, "JULI": 7, "JUL": 7, "AGUSTUS": 8, "AGU": 8, "SEPTEMBER": 9, "SEP": 9, "OKTOBER": 10, "OKT": 10, "NOVEMBER": 11, "NOV": 11, "DESEMBER": 12, "DES": 12}
    match = re.search(r"\b(\d{1,2})\s+([A-Z]+)\s+(\d{4})\b", value)
    if match and match.group(2) in months:
        try:
            return datetime(int(match.group(3)), months[match.group(2)], int(match.group(1))).strftime("%Y-%m-%d")
        except ValueError:
            return None
    return None


def _clean_value(value: str) -> str:
    value = re.sub(r"\s+", " ", value).strip(" :;.,-|")
    return re.sub(r"\s+(?:NIK|NAMA|ALAMAT|RT\s*/?\s*RW|KECAMATAN|KELURAHAN|DESA|AGAMA|PEKERJAAN)\b.*$", "", value, flags=re.I).strip(" :;.,-|")


def _contains_label(value: str) -> bool:
    return bool(re.search(r"\b(?:NIK|NAMA|ALAMAT|RT\s*/?\s*RW|KECAMATAN|KELURAHAN|DESA|AGAMA|PEKERJAAN)\b", value, re.I))


def _unique(items: list[str]) -> list[str]:
    return list(dict.fromkeys(item for item in items if item))

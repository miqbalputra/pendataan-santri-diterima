"""Evaluate exact-field accuracy and warm latency using only an authorized local dataset.

Manifest format (JSON Lines): {"path":"images/example.jpg","target":"ayah",
"quality":"clear","ground_truth":{"nik":"...","nama":"..."}}
This script prints aggregate metrics only and never prints recognized values.
"""

from __future__ import annotations

import argparse
import json
import resource
import time
from pathlib import Path

import numpy as np
from paddleocr import PaddleOCR

from document_parser import enhanced_variant, load_image, merge_candidates, parse_document, result_strength

FIELDS_BY_TARGET = {
    "ayah": ["nik", "nama", "tanggal_lahir", "alamat"],
    "ibu": ["nik", "nama", "tanggal_lahir", "alamat"],
    "kk": ["alamat"],
    "akta": ["nik", "nama", "tanggal_lahir"],
}


def normalize(value: str, key: str) -> str:
    value = " ".join(str(value or "").strip().split())
    return value if key == "nik" else value.casefold()


def infer(engine, image_bytes: bytes, target: str):
    image, warnings, quality = load_image(image_bytes, target)
    result = parse_document(engine.predict(image), target, auto_fill_enabled=False, quality_score=quality, quality_warnings=warnings)
    if result_strength(result)[0] < 2 or not result.get("document_check", {}).get("is_expected_document"):
        retry = parse_document(engine.predict(enhanced_variant(image)), target, auto_fill_enabled=False, quality_score=quality, quality_warnings=warnings)
        result = merge_candidates(result, retry)
    return result


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("manifest", type=Path, help="Path to consented JSONL ground-truth manifest")
    args = parser.parse_args()
    import paddle
    paddle.set_device("cpu")
    paddle.set_num_threads(2)
    engine = PaddleOCR(
        lang="id", ocr_version="PP-OCRv6", device="cpu",
        use_doc_orientation_classify=False, use_doc_unwarping=False, use_textline_orientation=False,
    )

    metrics: dict[str, dict[str, list[int]]] = {}
    sample_counts = {target: 0 for target in FIELDS_BY_TARGET}
    clear_counts = {target: 0 for target in FIELDS_BY_TARGET}
    timings: list[float] = []
    samples = 0
    for line_number, line in enumerate(args.manifest.read_text(encoding="utf-8").splitlines(), start=1):
        if not line.strip():
            continue
        item = json.loads(line)
        target = item.get("target")
        if target not in FIELDS_BY_TARGET:
            raise ValueError(f"Invalid target at manifest line {line_number}")
        sample_counts[target] += 1
        image_path = Path(item["path"])
        if not image_path.is_absolute():
            image_path = (args.manifest.parent / image_path).resolve()
        started = time.perf_counter()
        output = infer(engine, image_path.read_bytes(), target)
        timings.append(time.perf_counter() - started)
        samples += 1
        if item.get("quality") == "clear":
            clear_counts[target] += 1
            for field in FIELDS_BY_TARGET[target]:
                raw_expected = item.get("ground_truth", {}).get(field)
                if raw_expected is None or not str(raw_expected).strip():
                    continue
                expected = normalize(raw_expected, field)
                predicted = normalize(output.get("fields", {}).get(field, {}).get("value", ""), field)
                metric_name = f"{target}.{field}"
                entry = metrics.setdefault(metric_name, {"correct": [], "total": []})
                entry["correct"].append(int(predicted == expected))
                entry["total"].append(1)

    if not samples:
        raise ValueError("Manifest has no samples")
    report = {
        "samples": samples,
        "samples_by_target": sample_counts,
        "clear_samples_by_target": clear_counts,
        "clear_field_accuracy": {
            field: round(sum(values["correct"]) / max(1, sum(values["total"])), 4)
            for field, values in sorted(metrics.items())
        },
        "warm_ocr_seconds_p95": round(float(np.percentile(timings, 95)), 3),
        "peak_rss_mb": round(resource.getrusage(resource.RUSAGE_SELF).ru_maxrss / 1024, 1),
        "auto_fill_gate": "DISABLED unless NIK is 100% and every other critical field is at least 98% on clear samples",
    }
    print(json.dumps(report, indent=2, ensure_ascii=False))

    failures = []
    for target in FIELDS_BY_TARGET:
        if sample_counts[target] < 25:
            failures.append(f"{target}: needs at least 25 test documents")
        if clear_counts[target] < 8:
            failures.append(f"{target}: needs at least 8 clear samples for field accuracy evaluation")
        for field in FIELDS_BY_TARGET[target]:
            if field == "nik" and target == "akta":
                continue
            if not metrics.get(f"{target}.{field}", {}).get("total"):
                failures.append(f"{target}.{field}: missing clear ground truth")
    for field, values in metrics.items():
        accuracy = sum(values["correct"]) / max(1, sum(values["total"]))
        minimum = 1.0 if field.endswith(".nik") else 0.98
        if accuracy < minimum:
            failures.append(f"{field}: {accuracy:.1%} is below {minimum:.0%}")
    if np.percentile(timings, 95) > 15:
        failures.append(f"warm OCR p95 {np.percentile(timings, 95):.2f}s exceeds 15s")
    if failures:
        print("ACCEPTANCE FAILED: " + "; ".join(failures))
        return 2
    print("ACCEPTANCE PASSED. Review the report and approve auto-fill configuration separately.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

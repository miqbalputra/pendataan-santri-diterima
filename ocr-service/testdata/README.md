# OCR test data

Keep consented test scans on a secured machine and do not commit images or labels containing personal data. The evaluator accepts JSON Lines with `path`, `target`, `quality` (`clear`, `medium`, or `poor`), and a `ground_truth` object. The acceptance set should contain 25 documents for each target: `ayah`, `ibu`, `kk`, and `akta`, including clear, medium, and poor capture conditions.

Example (synthetic placeholders only):

```json
{"path":"images/consented-sample-001.jpg","target":"ayah","quality":"clear","ground_truth":{"nik":"0000000000000000","nama":"CONTOH","tanggal_lahir":"1980-01-01","alamat":"JALAN CONTOH"}}
```

Run inside the built OCR image with network access to the app's private Docker network disabled for external egress:

```sh
python /app/evaluate.py /secure/testdata/manifest.jsonl
```

Do not set `OCR_AUTO_FILL_ENABLED=true` unless all accuracy and latency gates pass on authorized data collected from the intended capture devices. Keep NIK auto-fill disabled if any clear-sample NIK differs.

import unittest

from document_parser import merge_candidates, parse_document


def prediction(lines, score=0.99):
    return [{
        "rec_texts": lines,
        "rec_scores": [score] * len(lines),
        "dt_polys": [
            [[0, index * 40], [800, index * 40], [800, index * 40 + 24], [0, index * 40 + 24]]
            for index in range(len(lines))
        ],
    }]


class DocumentParserTest(unittest.TestCase):
    def test_ktp_parent_outputs_confirmations_and_exact_nik_without_auto_gate(self):
        result = parse_document(prediction([
            "PROVINSI JAWA BARAT", "NIK : 3201234567890123", "NAMA : BUDI SANTOSO",
            "TEMPAT/TGL LAHIR : BANDUNG, 12-05-1980", "JENIS KELAMIN : LAKI-LAKI",
            "ALAMAT : JL MELATI 10", "RT/RW : 001/002", "KEL/DESA : SUKAMAJU",
            "KECAMATAN : BANDUNG WETAN",
        ]), "ayah", auto_fill_enabled=False)

        self.assertTrue(result["document_check"]["is_expected_document"])
        self.assertEqual(result["fields"]["nik"]["value"], "3201234567890123")
        self.assertEqual(result["fields"]["nik"]["decision"], "confirm")
        self.assertEqual(result["fields"]["nama"]["value"], "BUDI SANTOSO")
        self.assertEqual(result["fields"]["tanggal_lahir"]["value"], "1980-05-12")
        self.assertNotIn("nama_ibu", result["fields"])

    def test_automatic_fill_requires_explicit_gate_and_strong_scores(self):
        result = parse_document(prediction([
            "PROVINSI JAWA BARAT", "NIK : 3201234567890123", "NAMA : BUDI SANTOSO",
            "TEMPAT/TGL LAHIR : BANDUNG, 12-05-1980", "JENIS KELAMIN : LAKI-LAKI",
            "ALAMAT : JL MELATI 10", "RT/RW : 001/002", "KEL/DESA : SUKAMAJU",
            "KECAMATAN : BANDUNG WETAN",
        ]), "ayah", auto_fill_enabled=True)
        self.assertEqual(result["fields"]["nik"]["decision"], "auto")
        self.assertEqual(result["fields"]["nama"]["decision"], "auto")

    def test_wrong_parent_gender_blocks_all_suggestions(self):
        result = parse_document(prediction([
            "PROVINSI JAWA BARAT", "NIK : 3201234567890123", "NAMA : BUDI SANTOSO",
            "JENIS KELAMIN : PEREMPUAN", "ALAMAT : JL MELATI 10", "KECAMATAN : BANDUNG",
        ]), "ayah")
        self.assertFalse(result["document_check"]["is_expected_document"])
        self.assertEqual(result["fields"], {})

    def test_nik_is_never_repaired_when_digit_count_is_wrong(self):
        result = parse_document(prediction([
            "PROVINSI JAWA BARAT", "NIK : 320123456789012", "NAMA : BUDI SANTOSO",
            "JENIS KELAMIN : LAKI-LAKI", "ALAMAT : JL MELATI 10", "KECAMATAN : BANDUNG",
        ]), "ayah")
        self.assertNotIn("nik", result["fields"])

    def test_kk_only_suggests_address_fields(self):
        result = parse_document(prediction([
            "KARTU KELUARGA", "ALAMAT : JL MELATI 10", "RT/RW : 001/002",
            "KEL/DESA : SUKAMAJU", "KECAMATAN : BANDUNG WETAN",
        ]), "kk")
        self.assertTrue(result["document_check"]["is_expected_document"])
        self.assertIn("alamat", result["fields"])
        self.assertNotIn("nik", result["fields"])
        self.assertNotIn("nama", result["fields"])

    def test_akta_only_suggests_child_fields(self):
        result = parse_document(prediction([
            "KUTIPAN AKTA KELAHIRAN", "NAMA : ANAK CONTOH",
            "TEMPAT/TGL LAHIR : BANDUNG, 12-05-2018", "JENIS KELAMIN : PEREMPUAN",
        ]), "akta")
        self.assertTrue(result["document_check"]["is_expected_document"])
        self.assertEqual(result["fields"]["nama"]["value"], "ANAK CONTOH")
        self.assertNotIn("nama_ayah", result["fields"])

    def test_conflicting_retry_values_are_left_for_manual_entry(self):
        base = {"fields": {"nik": {"value": "3201234567890123", "confidence": 0.82, "decision": "confirm"}}, "warnings": []}
        alternate = {"fields": {"nik": {"value": "3201234567890124", "confidence": 0.80, "decision": "confirm"}}, "warnings": []}
        merged = merge_candidates(base, alternate)
        self.assertEqual(merged["fields"]["nik"]["value"], "")
        self.assertEqual(merged["fields"]["nik"]["decision"], "manual")


if __name__ == "__main__":
    unittest.main()

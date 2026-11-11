# OCR lokal pada VPS

Service OCR menggunakan PaddleOCR 3.7.0, PP-OCRv6 medium, dan bahasa Indonesia. PaddleOCR memuat model satu kali pada satu proses CPU. Container OCR dibatasi dua core dan 3 GB RAM, tidak mem-publish port ke host, dan berada pada jaringan Docker internal.

## Deployment

Repo ini belum memiliki Docker Compose utama, jadi `docker-compose.ocr.yml` adalah overlay. Tambahkan service `ocr` dan network `spsb_private` ke Compose deployment, lalu sambungkan container Laravel web dan tepat satu worker antrean ke network yang sama. Jangan menambahkan `ports` pada service OCR.

Buat token bersama 64 karakter heksadesimal dan pasang nilai yang sama pada service Laravel dan OCR. Contoh membuat token di Ubuntu:

```sh
openssl rand -hex 32
```

Set konfigurasi Laravel berikut di secret store deployment:

```dotenv
OCR_SERVICE_TOKEN=<token-yang-dihasilkan>
OCR_SERVICE_TIMEOUT=45
OCR_AUTO_FILL_ENABLED=false
QUEUE_CONNECTION=database
```

Deploy source dan jalankan migrasi additive sebelum membuka endpoint scan:

```sh
php artisan migrate --force
```

Pada service OCR, teruskan `OCR_SERVICE_TOKEN` dan `OCR_AUTO_FILL_ENABLED=false`. URL service Laravel dikunci ke `http://ocr:8000`; perubahan DNS tidak boleh mengarah ke host di luar network privat. Container OCR membangun cache model resmi pada image build, sehingga runtime tidak perlu mengunduh model. Jaringan internal Docker mencegah OCR mengirim gambar ke penyedia luar.

Jalankan satu worker Laravel khusus OCR:

```sh
php artisan queue:work database --queue=ocr --sleep=1 --tries=1 --timeout=55 --max-time=3600
```

Pastikan hanya satu replika worker OCR berjalan. `retry_after` queue database harus tetap lebih besar dari timeout 55 detik; konfigurasi bawaan repo adalah 90 detik. Jalankan scheduler Laravel setiap menit agar `ocr:cleanup` menghapus staging yang sudah berumur 24 jam:

```cron
* * * * * cd /path/to/laravel && php artisan schedule:run >> /dev/null 2>&1
```

Sebelum rilis, bangun image di lingkungan yang dapat mengakses registri Python dan unduhan model resmi. Setelah itu jalankan image pada network privat tanpa egress internet. Periksa `/healthz` hanya dari container yang terhubung ke network tersebut.

## Akurasi dan aktivasi auto-isi

Jangan memakai berkas produksi untuk evaluasi. Siapkan 100 dokumen uji yang penggunaannya disetujui: 25 KTP Bapak, 25 KTP Ibu, 25 KK, dan 25 Akta. Variasikan mutu foto, perangkat, dan pencahayaan. Simpan manifest serta gambar pada mesin uji yang aksesnya dibatasi; format manifest ada di `ocr-service/testdata/README.md`.

Jalankan `python ocr-service/evaluate.py /path/secure/manifest.jsonl` pada VPS sasaran. Evaluator mengeluarkan ringkasan agregat akurasi per kolom, p95 waktu OCR hangat, dan peak RSS; nilai dokumen tidak dicetak. Auto-isi baru boleh diaktifkan jika NIK tepat 100% pada sampel jelas yang layak, nama/tanggal lahir/alamat minimal 98%, dan p95 OCR hangat di bawah 15 detik. Aktifkan nilai environment pada Laravel dan service OCR secara bersamaan setelah hasil ditinjau. Ambang tidak memenuhi berarti tetap dalam mode konfirmasi/manual.

Belum ada dokumen berizin atau VPS target yang tersedia di repo ini. Karena itu akurasi, penggunaan memori aktual, dan target 15 detik belum dinyatakan lulus; auto-isi saat ini tetap nonaktif.

## Data dan pemulihan

Migrasi hanya menambah tabel `ocr_scans`; data pendaftar dan jalur berkas lama tidak diubah. Scan ID adalah UUID acak. Token lampiran tidak disimpan dalam bentuk asli pada database, terikat sesi dan jenis dokumen, serta kedaluwarsa setelah 24 jam. Hasil terstruktur saja disimpan sementara. Form tetap menerima upload langsung tanpa token untuk klien lama.

Jika antrean penuh, worker berhenti, atau OCR gagal, aplikasi tetap mempertahankan foto di staging dan memberi pilihan isi manual. Validasi pendaftaran gagal tidak menghapus staging, sehingga pengiriman bisa dicoba ulang. Setelah pendaftaran tersimpan, berkas dipindah ke public disk yang sudah digunakan aplikasi dan row/file staging dihapus.

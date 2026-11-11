# Foto dokumen dan OCR

Ketuk kotak dokumen untuk membuka kamera belakang. Sesuaikan dokumen dengan bingkai, ambil foto, periksa pratinjau, lalu pilih **Gunakan & Baca OCR**. Bingkai KTP dan KK melebar; bingkai Akta dan foto anak tegak. Hanya bagian di dalam bingkai yang digunakan. Foto anak dilampirkan tanpa ekstraksi teks.

Pemeriksaan di perangkat menandai resolusi rendah, gambar gelap/terlalu terang, dan tulisan yang tampak buram. Pemeriksaan ini merupakan perkiraan, sehingga hasil foto tetap perlu diperiksa pengguna. Foto ulang atau pilih **Isi Manual** jika hasilnya kurang jelas. Pilihan manual menyimpan lampiran yang dipilih dan membuka formulir tanpa menunggu OCR. Jika kamera tidak tersedia atau izin ditolak, pengguna dapat memilih berkas dari perangkat.

KK, KTP Bapak, KTP Ibu, Akta Anak, dan foto anak tetap wajib pada pendaftaran baru, termasuk saat mengisi manual. Pada halaman revisi, berkas lama tetap disimpan jika tidak diganti.

Persentase unggahan berasal dari transfer berkas. Setelah foto masuk staging privat, layar menunjukkan antrean, persiapan foto, pembacaan, dan pemeriksaan hasil. Pekerjaan OCR berjalan pada PaddleOCR lokal di VPS; jaringan OCR tidak memiliki akses keluar. Jika antrean atau model bermasalah, foto tetap dapat dipakai untuk mengisi data manual.

Resolusi maksimum gambar frontend 3200 piksel dengan kualitas JPEG 93%. Server memperbaiki orientasi EXIF, mencoba koreksi perspektif, meningkatkan kontras, dan melakukan satu pembacaan ulang jika hasil pertama belum memadai. Respons hanya berisi kolom terstruktur dan tingkat keyakinan; teks OCR mentah tidak disimpan. NIK tidak pernah diperbaiki dengan tebakan, KTP Bapak/Ibu mengisi data orang tua, Akta mengisi data anak, KK mengisi alamat, dan foto anak hanya menjadi lampiran.

Setiap saran OCR perlu diperiksa. Isi dengan keyakinan sedang meminta konfirmasi pilihan pengguna; nilai rendah tetap kosong untuk pengisian manual. Auto-isi kuat dinonaktifkan sampai evaluasi dataset berizin membuktikan target akurasi.

## Menjalankan dan memeriksa

Kamera memerlukan HTTPS atau localhost dan izin kamera dari pengguna. Aplikasi mengirim `Permissions-Policy: camera=(self)`; proxy hosting juga harus mengizinkan kamera untuk origin aplikasi.

Pengujian otomatis:

```sh
php vendor/phpunit/phpunit/phpunit --colors=never
node --test tests/js/document-scanner.test.cjs
```

Pengujian dengan kamera HP dan dokumen nyata masih diperlukan untuk menilai ketajaman kamera, ambang kualitas foto, serta akurasi dan latensi model pada VPS sasaran. Petunjuk deployment dan evaluasi ada di [ocr-local-vps.md](ocr-local-vps.md).

# Foto dokumen dan OCR

Ketuk kotak dokumen untuk membuka kamera belakang. Sesuaikan dokumen dengan bingkai, ambil foto, periksa pratinjau, lalu pilih **Gunakan & Baca OCR**. Bingkai KTP dan KK melebar; bingkai Akta dan foto anak tegak. Hanya bagian di dalam bingkai yang digunakan. Foto anak dilampirkan tanpa ekstraksi teks.

Pemeriksaan di perangkat menandai resolusi rendah, gambar gelap/terlalu terang, dan tulisan yang tampak buram. Pemeriksaan ini merupakan perkiraan, sehingga hasil foto tetap perlu diperiksa pengguna. Foto ulang atau pilih **Isi Manual** jika hasilnya kurang jelas. Pilihan manual menyimpan lampiran yang dipilih dan membuka formulir tanpa menunggu OCR. Jika kamera tidak tersedia atau izin ditolak, pengguna dapat memilih berkas dari perangkat.

KK, KTP Bapak, KTP Ibu, Akta Anak, dan foto anak tetap wajib pada pendaftaran baru, termasuk saat mengisi manual. Pada halaman revisi, berkas lama tetap disimpan jika tidak diganti.

Persentase unggahan berasal dari transfer berkas. Pembacaan OCR lokal menampilkan persentase dari Tesseract. Pembacaan Direct AI/n8n menampilkan indikator menunggu karena endpoint belum menyediakan progres proses di server. Hasil yang meragukan menawarkan pengisian manual; respons yang datang setelah pembatalan diabaikan.

Resolusi maksimum gambar frontend 3200 piksel dengan kualitas JPEG 93%. Direct AI mempertahankan hingga 3200 piksel untuk KK dan 2400 untuk dokumen lain, serta meminta pembacaan detail tinggi. Data JSON terstruktur tidak diproses ulang sebagai teks bebas. NIK yang tidak lengkap tidak diisi otomatis, dan KK hanya mengisi alamat agar identitas anggota keluarga tidak tertukar.

## Menjalankan dan memeriksa

Kamera memerlukan HTTPS atau localhost dan izin kamera dari pengguna. Aplikasi mengirim `Permissions-Policy: camera=(self)`; proxy hosting juga harus mengizinkan kamera untuk origin aplikasi.

Pengujian otomatis:

```sh
php vendor/phpunit/phpunit/phpunit --colors=never
node --test tests/js/document-scanner.test.cjs
```

Pengujian dengan kamera HP dan dokumen nyata masih diperlukan untuk menilai ketajaman kamera, ambang kualitas foto, serta akurasi model OCR yang dikonfigurasi.

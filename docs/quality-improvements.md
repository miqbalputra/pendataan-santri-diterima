# Akurasi, kecepatan, dan kemudahan pengisian

Perubahan ini tidak memerlukan migrasi database atau perubahan data lama.

## Pengisian dan penyimpanan

- Formulir hanya menerima kolom data pengguna yang diizinkan. Status verifikasi, periode, token revisi, dan jalur berkas ditentukan server.
- Format NIK, tanggal, email, WhatsApp, RT/RW, kode pos, serta data periodik diperiksa. NIK anak dan orang tua harus berbeda. Nomor WhatsApp dinormalisasi hanya untuk format yang dikenal; aplikasi tidak menebak pengganti nomor yang salah.
- Koreksi manual tidak ditimpa hasil OCR yang datang kemudian. Mengganti dokumen membersihkan hasil OCR lama dari dokumen tersebut, tanpa menghapus koreksi manual atau data dokumen lain.
- Ringkasan identitas, kontak, dan alamat ditampilkan sebelum pengiriman. Data sekolah sebelumnya, email, dusun, dan hobi diberi label opsional.
- Kesalahan ditandai di dekat kolom, dengan fokus diarahkan ke bagian yang perlu diperbaiki. Pengiriman menggunakan AJAX sehingga foto, tanda tangan, dan isian tetap tersedia saat validasi gagal atau koneksi terputus.
- Pendaftaran baru tetap memerlukan lima lampiran. Penyimpanan memakai transaksi database, pemeriksaan ulang kuota dengan kunci baris pada database yang mendukungnya, dan pembersihan berkas baru bila penyimpanan gagal. Berkas lama tidak dibersihkan oleh alur ini.

## Kecepatan

- Pengaturan halaman publik dibaca dalam satu query, sebelumnya tiga. Dashboard juga mengambil pengaturan sekaligus dan menghitung statistik lewat agregasi.
- Daftar tindak lanjut difilter di database dan dibatasi 100 baris, sehingga tidak memuat seluruh pendaftar ke memori.
- Notifikasi diproses dengan deferred callback setelah respons dikirim. Kegagalan webhook dicatat dan tidak membatalkan pendaftaran yang sudah tersimpan. Callback ini bukan antrean persisten dan tidak menyediakan retry otomatis.
- Tailwind dikompilasi saat build. JavaScript SweetAlert dan Signature Pad disajikan dari aplikasi sendiri. Tesseract serta worker/core dimuat lokal saat OCR diperlukan; data bahasa Tesseract masih diunduh saat penggunaan pertama.
- Konfigurasi nginx menambahkan gzip serta cache aset build. Header ini baru aktif setelah konfigurasi dipasang di server.
- Stylesheet font eksternal dimuat tanpa menahan tampilan awal; browser memakai font pengganti saat jaringan font belum siap.

## Build dan pemeriksaan

Jalankan `npm ci --ignore-scripts` lalu `npm run build` untuk menghasilkan CSS dan menyalin pustaka browser ke `public/vendor`. Direktori build dan vendor tersebut merupakan hasil build, bukan source yang disimpan di Git. Jangan melewatkan langkah build saat deployment.

```sh
php vendor/phpunit/phpunit/phpunit --colors=never
node --test tests/js/document-scanner.test.cjs tests/js/form-helper.test.cjs
npm audit
```

PHPUnit memakai SQLite di memori sesuai `phpunit.xml`; pengujian tidak memakai database produksi. Pemeriksaan browser menggunakan halaman dari database sementara, mencakup pengisian manual, validasi, fokus kesalahan, dan viewport ponsel 390 piksel.

Akurasi dokumen nyata, kamera HP, beban serentak pada MariaDB, latensi jaringan, serta konfigurasi hosting tetap perlu diukur pada lingkungan tujuan. Validasi format dan konfirmasi manusia membantu mencegah kesalahan; aplikasi tidak memverifikasi identitas terhadap data kependudukan resmi.

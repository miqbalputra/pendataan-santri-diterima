# Dashboard administrasi SPSB

Dashboard menggunakan Laravel 13 dan Blade. Perubahan tidak memerlukan migrasi database, tidak mengubah data pendaftar lama, dan mempertahankan alur pengaturan, periode, verifikasi, revisi, serta sampah.

## Cara kerja

- Ringkasan teratas menghitung seluruh data aktif. Angka hasil pencarian di atas tabel hanya menghitung hasil filter; keduanya diberi keterangan terpisah.
- Pencarian mencakup nomor pendaftaran, NIK anak/orang tua/wali, nama keluarga, kontak, email, sekolah, dan alamat. Beberapa kata dicari bersama; karakter `%` dan `_` diperlakukan sebagai teks. Nomor WhatsApp berformat `+62` dapat menemukan nomor lokal `08`.
- Filter status, jenis kelamin, periode, gelombang, kondisi dokumen, tindak lanjut, notifikasi, dan tanggal dapat digabungkan. Batas tanggal mengikuti hari kalender WIB, kemudian dikonversi ke zona waktu aplikasi untuk query.
- Tabel memiliki urutan stabil serta pilihan 20/50/100 baris. Pada ponsel, peserta ditampilkan sebagai kartu. Data relasi dimuat bersama untuk mencegah query per baris.
- Ekspor memakai logika filter yang sama dengan daftar. CSV memuat isian lengkap dan nama periode/gelombang; masukan yang menyerupai formula tidak dieksekusi sebagai formula spreadsheet. Excel mempertahankan tipe teks untuk identitas dan tidak menyertakan token revisi.
- Tindak lanjut, log aktivitas, log notifikasi, dan riwayat notifikasi pada detail dapat ditelusuri per halaman. Tidak ada pemotongan diam-diam pada 100 catatan atau delapan notifikasi.
- Detail peserta menampilkan seluruh kolom formulir, termasuk keluarga/wali, dengan pencarian kolom/nilai di browser. Tautan kembali mempertahankan pencarian dan halaman daftar.

## Arti status

- **Sudah diverifikasi** adalah status pendataan `Diterima` yang ditetapkan panitia; kelengkapan/validitas dokumen dihitung terpisah.
- **Lampiran tersedia** berarti jalur lampiran tercatat. **Valid** berarti dokumen tersebut ditandai valid dan jalurnya tidak kosong. Akses berkas tetap diperiksa oleh endpoint dokumen.
- Dokumen kosong tidak dapat ditandai valid. Pembaruan sebagian status dokumen mempertahankan status dokumen lain.
- **Masuk grup dikonfirmasi** berasal dari checklist panitia. Membuka tautan grup tidak otomatis mengubah checklist ini.
- **Webhook terakhir** mengacu pada catatan terakhir peserta, bukan seluruh riwayat. `success` berarti layanan menerima request, bukan bukti pesan terkirim ke perangkat atau sudah dibaca. Riwayat detail tersedia untuk pemeriksaan lebih lanjut.

## Pemeriksaan

```sh
php vendor/phpunit/phpunit/phpunit --colors=never
node --test tests/js/document-scanner.test.cjs tests/js/form-helper.test.cjs
npm run build
```

Pengujian memakai SQLite di memori dan data contoh. Pemeriksaan browser mencakup pencarian, filter gabungan, hasil kosong, pencarian kolom detail, navigasi tindak lanjut, serta tampilan desktop dan ponsel 390 piksel. Halaman admin memakai `Cache-Control: private, no-store` dan tetap memerlukan autentikasi.

Pengukuran beban pada MariaDB/server produksi dan kebijakan operasional seperti pembagian peran admin, retensi audit, dan backup perlu ditetapkan sesuai kebutuhan lembaga. Perubahan UI ini bukan sertifikasi keamanan atau jaminan SLA.

## Rujukan Context7

- [Laravel 13 — Eloquent relationships](https://laravel.com/docs/13.x/eloquent-relationships): eager loading, `latestOfMany`, dan filter relasi.
- [Laravel 13 — Pagination](https://laravel.com/docs/13.x/pagination): mempertahankan query dan paginator dengan nama berbeda.
- [Laravel 13 — Query Builder](https://laravel.com/docs/13.x/queries): pengelompokan kondisi serta filter kolom JSON.

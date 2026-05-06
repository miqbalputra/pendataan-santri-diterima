<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pendaftar: {{ $santri->nama_lengkap }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .form-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            background-color: #f8fafc;
        }
        .form-input:focus {
            outline: none;
            border-color: #10b981;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }
        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 pb-20">
    <div class="max-w-5xl mx-auto py-10 px-4">
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3 text-xs font-medium text-slate-500">
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-600 transition">Dashboard</a></li>
                        <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg></li>
                        <li><a href="{{ route('admin.show', $santri->id) }}" class="hover:text-emerald-600 transition">Detail Pendaftar</a></li>
                        <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg></li>
                        <li class="text-slate-800">Edit Data</li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Edit Data Pendaftar</h1>
                <p class="text-slate-500 mt-1">Mengubah informasi untuk <span class="font-bold text-emerald-600">{{ $santri->nama_lengkap }}</span></p>
            </div>
            <a href="{{ route('admin.show', $santri->id) }}" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Batal & Kembali
            </a>
        </div>

        <form action="{{ route('admin.update', $santri->id) }}" method="POST">
            @csrf
            <div class="space-y-8">
                
                <!-- I. IDENTITAS ANAK -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-emerald-600 px-6 py-4">
                        <h3 class="font-bold text-white uppercase tracking-wider text-sm">I. Identitas Peserta Didik</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-input" value="{{ $santri->nama_lengkap }}"></div>
                            <div><label class="form-label">NIK Anak</label><input type="text" name="nik_anak" class="form-input" value="{{ $santri->nik_anak }}"></div>
                            <div>
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-input">
                                    <option value="Laki-laki" {{ $santri->jenis_kelamin == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ $santri->jenis_kelamin == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div><label class="form-label">Tempat Lahir</label><input type="text" name="tempat_lahir" class="form-input" value="{{ $santri->tempat_lahir }}"></div>
                            <div><label class="form-label">Tanggal Lahir</label><input type="date" name="tanggal_lahir" class="form-input" value="{{ $santri->tanggal_lahir }}"></div>
                            <div><label class="form-label">NISN</label><input type="text" name="nisn" class="form-input" value="{{ $santri->nisn }}"></div>
                            <div><label class="form-label">Agama</label><input type="text" name="agama" class="form-input" value="{{ $santri->agama }}"></div>
                            <div><label class="form-label">Hobi</label><input type="text" name="hobi" class="form-input" value="{{ $santri->hobi }}"></div>
                            <div><label class="form-label">Berkebutuhan Khusus</label><input type="text" name="berkebutuhan_khusus" class="form-input" value="{{ $santri->berkebutuhan_khusus }}"></div>
                        </div>

                        <div class="pt-6 border-t">
                            <h4 class="text-xs font-bold text-slate-400 uppercase mb-4">Informasi Sekolah Asal</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                                <div class="lg:col-span-2"><label class="form-label">Nama Sekolah Asal</label><input type="text" name="nama_sekolah_asal" class="form-input" value="{{ $santri->nama_sekolah_asal }}"></div>
                                <div><label class="form-label">NPSN Sekolah</label><input type="text" name="npsn_sekolah_asal" class="form-input" value="{{ $santri->npsn_sekolah_asal }}"></div>
                                <div><label class="form-label">No. Seri Ijazah</label><input type="text" name="no_seri_ijazah" class="form-input" value="{{ $santri->no_seri_ijazah }}"></div>
                                <div class="lg:col-span-4"><label class="form-label">Alamat Sekolah Asal</label><input type="text" name="alamat_sekolah_asal" class="form-input" value="{{ $santri->alamat_sekolah_asal }}"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- II. ALAMAT -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-slate-800 px-6 py-4">
                        <h3 class="font-bold text-white uppercase tracking-wider text-sm">II. Alamat Tempat Tinggal</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div class="lg:col-span-4"><label class="form-label">Alamat Lengkap</label><textarea name="alamat_lengkap" rows="2" class="form-input">{{ $santri->alamat_lengkap }}</textarea></div>
                        <div><label class="form-label">Dusun</label><input type="text" name="dusun" class="form-input" value="{{ $santri->dusun }}"></div>
                        <div><label class="form-label">RT / RW</label><input type="text" name="rt_rw" class="form-input" value="{{ $santri->rt_rw }}"></div>
                        <div><label class="form-label">Kelurahan / Desa</label><input type="text" name="kelurahan_desa" class="form-input" value="{{ $santri->kelurahan_desa }}"></div>
                        <div><label class="form-label">Kecamatan</label><input type="text" name="kecamatan" class="form-input" value="{{ $santri->kecamatan }}"></div>
                        <div><label class="form-label">Kabupaten / Kota</label><input type="text" name="kabupaten_kota" class="form-input" value="{{ $santri->kabupaten_kota }}"></div>
                        <div><label class="form-label">Propinsi</label><input type="text" name="propinsi" class="form-input" value="{{ $santri->propinsi }}"></div>
                        <div><label class="form-label">Kode Pos</label><input type="text" name="kode_pos" class="form-input" value="{{ $santri->kode_pos }}"></div>
                        <div><label class="form-label">Transportasi</label><input type="text" name="alat_transportasi" class="form-input" value="{{ $santri->alat_transportasi }}"></div>
                    </div>
                </div>

                <!-- III. DATA AYAH -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden border-l-4 border-blue-500">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h3 class="font-bold text-blue-900 uppercase tracking-wider text-sm">III. Data Ayah Kandung</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div><label class="form-label">Nama Ayah</label><input type="text" name="nama_ayah" class="form-input" value="{{ $santri->nama_ayah }}"></div>
                        <div><label class="form-label">NIK Ayah</label><input type="text" name="nik_ayah" class="form-input" value="{{ $santri->nik_ayah }}"></div>
                        <div><label class="form-label">No. WA Ayah</label><input type="text" name="no_wa_ayah" class="form-input" value="{{ $santri->no_wa_ayah }}"></div>
                        <div><label class="form-label">Email Ayah</label><input type="email" name="email_ayah" class="form-input" value="{{ $santri->email_ayah }}"></div>
                        <div><label class="form-label">Pekerjaan Ayah</label><input type="text" name="pekerjaan_ayah" class="form-input" value="{{ $santri->pekerjaan_ayah }}"></div>
                        <div><label class="form-label">Pendidikan Ayah</label><input type="text" name="pendidikan_ayah" class="form-input" value="{{ $santri->pendidikan_ayah }}"></div>
                        <div>
                            <label class="form-label">Status Tahsin Ayah</label>
                            <select name="status_tahsin_ayah" class="form-input">
                                <option value="Belum" {{ $santri->status_tahsin_ayah == 'Belum' ? 'selected' : '' }}>Belum</option>
                                <option value="Sudah" {{ $santri->status_tahsin_ayah == 'Sudah' ? 'selected' : '' }}>Sudah</option>
                            </select>
                        </div>
                        <div><label class="form-label">Pengajar Tahsin Ayah</label><input type="text" name="pengajar_tahsin_ayah" class="form-input" value="{{ $santri->pengajar_tahsin_ayah }}"></div>
                    </div>
                </div>

                <!-- IV. DATA IBU -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden border-l-4 border-pink-500">
                    <div class="bg-pink-50 px-6 py-4 border-b">
                        <h3 class="font-bold text-pink-900 uppercase tracking-wider text-sm">IV. Data Ibu Kandung</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div><label class="form-label">Nama Ibu</label><input type="text" name="nama_ibu" class="form-input" value="{{ $santri->nama_ibu }}"></div>
                        <div><label class="form-label">NIK Ibu</label><input type="text" name="nik_ibu" class="form-input" value="{{ $santri->nik_ibu }}"></div>
                        <div><label class="form-label">No. WA Ibu</label><input type="text" name="no_wa_ibu" class="form-input" value="{{ $santri->no_wa_ibu }}"></div>
                        <div><label class="form-label">Email Ibu</label><input type="email" name="email_ibu" class="form-input" value="{{ $santri->email_ibu }}"></div>
                        <div><label class="form-label">Pekerjaan Ibu</label><input type="text" name="pekerjaan_ibu" class="form-input" value="{{ $santri->pekerjaan_ibu }}"></div>
                        <div><label class="form-label">Pendidikan Ibu</label><input type="text" name="pendidikan_ibu" class="form-input" value="{{ $santri->pendidikan_ibu }}"></div>
                        <div>
                            <label class="form-label">Status Tahsin Ibu</label>
                            <select name="status_tahsin_ibu" class="form-input">
                                <option value="Belum" {{ $santri->status_tahsin_ibu == 'Belum' ? 'selected' : '' }}>Belum</option>
                                <option value="Sudah" {{ $santri->status_tahsin_ibu == 'Sudah' ? 'selected' : '' }}>Sudah</option>
                            </select>
                        </div>
                        <div><label class="form-label">Pengajar Tahsin Ibu</label><input type="text" name="pengajar_tahsin_ibu" class="form-input" value="{{ $santri->pengajar_tahsin_ibu }}"></div>
                    </div>
                </div>

                <!-- V. DATA PERIODIK -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden border-l-4 border-emerald-500">
                    <div class="bg-emerald-50 px-6 py-4 border-b">
                        <h3 class="font-bold text-emerald-900 uppercase tracking-wider text-sm">V. Data Periodik</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-5 gap-5">
                        <div><label class="form-label">Tinggi (cm)</label><input type="number" name="tinggi_badan" class="form-input" value="{{ $santri->tinggi_badan }}"></div>
                        <div><label class="form-label">Berat (kg)</label><input type="number" name="berat_badan" class="form-input" value="{{ $santri->berat_badan }}"></div>
                        <div><label class="form-label">Jarak</label><input type="text" name="jarak_ke_sekolah" class="form-input" value="{{ $santri->jarak_ke_sekolah }}"></div>
                        <div><label class="form-label">Waktu Tempuh</label><input type="text" name="waktu_tempuh" class="form-input" value="{{ $santri->waktu_tempuh }}"></div>
                        <div><label class="form-label">Jml Saudara</label><input type="number" name="jumlah_saudara_kandung" class="form-input" value="{{ $santri->jumlah_saudara_kandung }}"></div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex justify-end gap-4 mt-10">
                    <a href="{{ route('admin.show', $santri->id) }}" class="px-8 py-4 bg-slate-200 hover:bg-slate-300 rounded-2xl font-bold text-slate-700 transition">Batal</a>
                    <button type="submit" class="px-12 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold shadow-xl shadow-emerald-500/20 transition-all hover:-translate-y-1">Simpan Perubahan</button>
                </div>
                <p class="text-xs text-slate-400 text-center">*Dokumen foto tidak dapat diubah di halaman ini demi integritas data.</p>
            </div>
        </form>
    </div>
</body>
</html>

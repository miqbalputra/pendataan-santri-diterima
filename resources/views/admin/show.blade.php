<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pendaftar: {{ $santri->nama_lengkap }}</title>
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
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 pb-20">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3 text-xs font-medium text-slate-500">
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-600 transition">Dashboard</a></li>
                        <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg></li>
                        <li class="text-slate-800">Detail Pendaftar</li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Detail Peserta Didik: <span class="text-emerald-600">{{ $santri->nama_lengkap }}</span></h1>
                <p class="text-slate-500 mt-1">ID Pendaftaran: #PSB-{{ str_pad($santri->id, 5, '0', STR_PAD_LEFT) }} | Terdaftar pada: {{ $santri->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali
                </a>
                <a href="{{ route('admin.edit', $santri->id) }}" class="px-5 py-2.5 bg-slate-900 text-white hover:bg-slate-800 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Edit Data
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Data Details -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- I. IDENTITAS ANAK -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-emerald-600 px-6 py-4 flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-lg"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div>
                        <h3 class="font-bold text-white uppercase tracking-wider text-sm">I. Identitas Peserta Didik</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Nama Lengkap</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->nama_lengkap }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">NIK</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->nik }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Jenis Kelamin</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->jenis_kelamin }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Tempat, Tgl Lahir</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->tempat_lahir . ', ' . \Carbon\Carbon::parse($santri->tanggal_lahir)->format('d F Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">NISN</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->nisn ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Agama</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->agama ?? 'Islam' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Berkebutuhan Khusus</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->berkebutuhan_khusus ?? 'Tidak Ada' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Hobi</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->hobi ?? '-' }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <h4 class="text-xs font-bold text-slate-400 uppercase mb-4">Sekolah Asal / Jenjang Sebelumnya</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Nama Sekolah Asal</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $santri->nama_sekolah_asal ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">NPSN Sekolah</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $santri->npsn_sekolah_asal ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">No. Ijazah</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $santri->no_seri_ijazah ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">No. UN</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $santri->no_ujian_nasional ?? '-' }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <div>
                                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Alamat Sekolah</p>
                                        <p class="text-sm font-bold text-slate-800">{{ $santri->alamat_sekolah_asal ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- II. ALAMAT TEMPAT TINGGAL -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-slate-800 px-6 py-4 flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-lg"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></div>
                        <h3 class="font-bold text-white uppercase tracking-wider text-sm">II. Alamat Tempat Tinggal</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="md:col-span-2">
                                <div>
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Alamat Lengkap</p>
                                    <p class="text-sm font-bold text-slate-800">{{ $santri->alamat_lengkap }}</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Dusun</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->dusun ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">RT / RW</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->rt_rw ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Kelurahan / Desa</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->kelurahan_desa ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Kecamatan</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->kecamatan ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Kabupaten / Kota</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->kabupaten_kota ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Propinsi</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->propinsi ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Kode Pos</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->kode_pos ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Jenis Tinggal</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->jenis_tinggal ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Alat Transportasi</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->alat_transportasi ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- III. DATA ORANG TUA -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- AYAH -->
                    <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
                        <div class="bg-blue-600 px-6 py-4 flex items-center gap-3">
                            <h3 class="font-bold text-white uppercase tracking-wider text-xs">III. Data Ayah Kandung</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Nama Ayah</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->nama_ayah }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">NIK</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->nik_ayah ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">No WA</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->no_wa_ayah ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Email</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->email_ayah ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Pekerjaan</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->pekerjaan_ayah ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Pendidikan</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->pendidikan_ayah ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Penghasilan</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->penghasilan_ayah ?? '-' }}</p>
                            </div>
                            <div class="pt-2 border-t mt-2">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Status Tahsin</p>
                                <p class="text-sm font-semibold {{ $santri->status_tahsin_ayah == 'Sudah' ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ $santri->status_tahsin_ayah }} 
                                    @if($santri->status_tahsin_ayah == 'Sudah' && $santri->pengajar_tahsin_ayah)
                                        <span class="text-slate-500 font-normal">({{ $santri->pengajar_tahsin_ayah }})</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- IBU -->
                    <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
                        <div class="bg-pink-600 px-6 py-4 flex items-center gap-3">
                            <h3 class="font-bold text-white uppercase tracking-wider text-xs">IV. Data Ibu Kandung</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Nama Ibu</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->nama_ibu }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">NIK</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->nik_ibu ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">No WA</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->no_wa_ibu ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Email</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->email_ibu ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Pekerjaan</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->pekerjaan_ibu ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Pendidikan</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->pendidikan_ibu ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Penghasilan</p>
                                <p class="text-sm font-bold text-slate-800">{{ $santri->penghasilan_ibu ?? '-' }}</p>
                            </div>
                            <div class="pt-2 border-t mt-2">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Status Tahsin</p>
                                <p class="text-sm font-semibold {{ $santri->status_tahsin_ibu == 'Sudah' ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ $santri->status_tahsin_ibu }} 
                                    @if($santri->status_tahsin_ibu == 'Sudah' && $santri->pengajar_tahsin_ibu)
                                        <span class="text-slate-500 font-normal">({{ $santri->pengajar_tahsin_ibu }})</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IV. DATA WALI (IF EXISTS) -->
                @if($santri->nama_wali)
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden border-l-4 border-indigo-500">
                    <div class="bg-indigo-50 px-6 py-4 flex items-center gap-3 border-b">
                        <h3 class="font-bold text-indigo-900 uppercase tracking-wider text-sm">V. Data Wali</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Nama Wali</p>
                            <p class="text-sm font-bold text-slate-800">{{ $santri->nama_wali }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">NIK Wali</p>
                            <p class="text-sm font-bold text-slate-800">{{ $santri->nik_wali ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Pekerjaan</p>
                            <p class="text-sm font-bold text-slate-800">{{ $santri->pekerjaan_wali ?? '-' }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- V. DATA PERIODIK -->
                <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-emerald-50 px-6 py-4 border-b flex items-center gap-3">
                        <h3 class="font-bold text-emerald-900 uppercase tracking-wider text-sm">VI. Data Periodik</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-5 gap-6">
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Tinggi</p>
                            <p class="text-sm font-bold text-slate-800">{{ ($santri->tinggi_badan ?? '-') . ' cm' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Berat</p>
                            <p class="text-sm font-bold text-slate-800">{{ ($santri->berat_badan ?? '-') . ' kg' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Jarak</p>
                            <p class="text-sm font-bold text-slate-800">{{ $santri->jarak_ke_sekolah ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Waktu</p>
                            <p class="text-sm font-bold text-slate-800">{{ $santri->waktu_tempuh ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-0.5">Saudara</p>
                            <p class="text-sm font-bold text-slate-800">{{ ($santri->jumlah_saudara_kandung ?? '0') . ' Orang' }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Documents & Verification -->
            <div class="space-y-8">
                
                <!-- STATUS CONTROL -->
                <div class="glass-card rounded-2xl shadow-lg p-6 border-t-4 border-emerald-500 sticky top-6">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Verifikasi Pendaftaran
                    </h3>
                    
                    <form action="{{ route('admin.status', $santri->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Ubah Status</label>
                            <select name="status_pendaftaran" class="w-full border-slate-200 rounded-xl py-3 px-4 bg-slate-50 font-bold text-slate-700 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner">
                                <option value="Pending" {{ $santri->status_pendaftaran == 'Pending' ? 'selected' : '' }}>PENDING (MENUNGGU)</option>
                                <option value="Diterima" {{ $santri->status_pendaftaran == 'Diterima' ? 'selected' : '' }}>DITERIMA</option>
                                <option value="Ditolak" {{ $santri->status_pendaftaran == 'Ditolak' ? 'selected' : '' }}>DITOLAK</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-emerald-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0">
                            Simpan Verifikasi
                        </button>
                    </form>

                    <div class="mt-6 pt-6 border-t border-slate-100 flex flex-col gap-3">
                        @php
                            $waNumber = preg_replace('/^08/', '628', $santri->no_wa_ayah);
                            $waText = urlencode("Assalamu'alaikum Bapak/Ibu {$santri->nama_ayah}, kami dari Panitia PSB Griya Qur'an.\n\nTerkait pendaftaran ananda *{$santri->nama_lengkap}*, [Sebutkan Berita].");
                        @endphp
                        <a href="https://wa.me/{{ $waNumber }}?text={{ $waText }}" target="_blank" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2 text-sm shadow-md">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.669-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            Hubungi via WhatsApp
                        </a>
                        <a href="{{ route('pendaftaran.cetak', $santri->id) }}" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Cetak Formulir (PDF)
                        </a>
                    </div>
                </div>

                <!-- DOCUMENTS -->
                <div class="glass-card rounded-2xl shadow-sm p-6">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Berkas Digital
                    </h3>
                    <div class="space-y-4">
                        @php
                            $docs = [
                                ['label' => 'Pas Foto Anak', 'path' => $santri->foto_pas_siswa, 'icon' => 'user'],
                                ['label' => 'KTP Ayah', 'path' => $santri->foto_ktp_ayah, 'icon' => 'id'],
                                ['label' => 'KTP Ibu', 'path' => $santri->foto_ktp_ibu, 'icon' => 'id'],
                                ['label' => 'Akta Kelahiran', 'path' => $santri->foto_akta_anak, 'icon' => 'file'],
                                ['label' => 'Kartu Keluarga', 'path' => $santri->foto_kk, 'icon' => 'home']
                            ];
                        @endphp

                        @foreach($docs as $doc)
                        <div class="border border-slate-100 rounded-xl p-3 bg-slate-50 group transition hover:bg-white hover:shadow-md">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-tight">{{ $doc['label'] }}</span>
                                @if($doc['path'])
                                    <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">TERSEDIA</span>
                                @else
                                    <span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">KOSONG</span>
                                @endif
                            </div>
                            @if($doc['path'])
                                <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="block relative overflow-hidden rounded-lg border bg-white group-hover:border-blue-300 transition-colors">
                                    <img src="{{ Storage::url($doc['path']) }}" class="w-full h-32 object-contain group-hover:scale-105 transition-transform duration-500" alt="{{ $doc['label'] }}">
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                        <span class="text-white text-xs font-bold bg-white/20 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/30">Klik untuk Perbesar</span>
                                    </div>
                                </a>
                            @else
                                <div class="h-32 bg-slate-100 rounded-lg flex flex-col items-center justify-center text-slate-400 border border-dashed">
                                    <svg class="w-8 h-8 mb-1 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span class="text-xs italic font-medium">Dokumen belum diunggah</span>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- TANDA TANGAN & PERNYATAAN -->
                <div class="glass-card rounded-2xl shadow-lg p-6 overflow-hidden relative">
                    <div class="absolute -right-6 -top-6 w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </div>
                    <h3 class="font-bold text-lg mb-4">Pengesahan Orang Tua</h3>
                    
                    <div class="space-y-6">
                        <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 flex items-start gap-3">
                            <div class="mt-1">
                                @if($santri->pernyataan_kebenaran_data)
                                    <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                @else
                                    <svg class="w-5 h-5 text-slate-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs font-bold text-emerald-800 uppercase">Ceklist Kebenaran Data</p>
                                <p class="text-xs text-emerald-700 leading-relaxed mt-1">Orang tua menyatakan seluruh data benar & bertanggung jawab secara hukum.</p>
                            </div>
                        </div>

                        <div class="border rounded-xl p-4 bg-white shadow-inner">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Tanda Tangan Digital</p>
                            @if($santri->tanda_tangan)
                                <img src="{{ Storage::url($santri->tanda_tangan) }}" class="h-32 mx-auto object-contain" alt="Tanda Tangan">
                            @else
                                <div class="h-32 flex items-center justify-center bg-slate-50 text-slate-300 italic text-xs">Belum ada tanda tangan</div>
                            @endif
                            <div class="mt-2 pt-2 border-t text-center">
                                <p class="text-sm font-bold text-slate-800">{{ $santri->penandatangan_nama ?? '-' }}</p>
                                <p class="text-[10px] text-slate-400 font-medium italic">Penandatangan</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>


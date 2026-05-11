<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPSB | Pendataan Peserta Didik Baru</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #047857;
            --primary-dark: #064e3b;
            --accent: #0f766e;
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f3f7f4;
            color: #1e293b;
        }
        .mesh-gradient {
            background: linear-gradient(135deg, #064e3b 0%, #047857 58%, #0f766e 100%);
            position: relative;
            overflow: hidden;
        }
        .mesh-gradient::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
            opacity: 0.05;
            pointer-events: none;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.05);
        }
        .spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 3px solid #10b981;
            width: 24px; height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade { animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .progress-glow { box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); }
        .required-mark::after { content: " *"; color: #ef4444; font-weight: bold; }
        
        .form-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            font-size: 1rem !important;
            transition: all 0.2s;
            background-color: #f8fafc;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.16);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.format-rupiah').forEach(input => {
                input.addEventListener('keyup', function(e) {
                    let val = this.value.replace(/[^,\d]/g, '').toString();
                    if (val) {
                        let split = val.split(','), sisa = split[0].length % 3, rupiah = split[0].substr(0, sisa), ribuan = split[0].substr(sisa).match(/\d{3}/gi);
                        if (ribuan) { let separator = sisa ? '.' : ''; rupiah += separator + ribuan.join('.'); }
                        this.value = 'Rp. ' + (split[1] != undefined ? rupiah + ',' + split[1] : rupiah);
                    }
                });
            });

            document.querySelectorAll('.format-wa').forEach(input => {
                input.addEventListener('input', function() {
                    let val = this.value.replace(/\D/g, '');
                    if (val.length > 0) {
                        if (val.startsWith('8')) val = '0' + val;
                        else if (val.startsWith('628')) val = '08' + val.substring(3);
                    }
                    this.value = val;
                });
            });

            updateProgress();
            document.addEventListener('input', (e) => { if (e.target.matches('input, select, textarea')) updateProgress(); });
        });
    </script>
</head>
<body class="bg-[#f3f7f4]">
    <!-- Floating Progress Pill (Bottom) -->
    <div id="progress-container" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-md bg-slate-900/80 backdrop-blur-xl border border-white/10 p-2.5 rounded-2xl shadow-2xl shadow-emerald-900/20 transition-transform duration-700">
        <div class="flex justify-between items-center mb-2 px-2">
            <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Progres Formulir</span>
            <span class="text-xs font-bold text-emerald-400" id="progress-text">0%</span>
        </div>
        <div class="w-full bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
            <div id="progress-bar" class="bg-gradient-to-r from-emerald-400 to-teal-300 h-full rounded-full transition-all duration-700 ease-out progress-glow" style="width: 0%"></div>
        </div>
    </div>

    <!-- Hero Header Section -->
    <div class="mesh-gradient pt-10 pb-32 px-4 sm:px-6 relative overflow-hidden">
        <div class="max-w-4xl mx-auto relative z-10 text-center animate-fade">
            <div class="flex justify-center gap-3 mb-8">
                <a href="{{ route('pendaftaran.cek_status') }}" class="bg-white/10 hover:bg-white/15 text-white border border-white/20 px-4 py-2 rounded-xl text-xs font-bold transition">Cek Status Data</a>
            </div>

            <div class="inline-flex items-center gap-2.5 bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-emerald-50 mb-8 border border-white/15 shadow-lg">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                </span>
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-widest text-emerald-100">Pendataan Online SPSB</span>
            </div>
            
            <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-5 tracking-tight leading-[1.1] drop-shadow-sm">
                Formulir Pendataan Peserta Didik Baru
            </h1>
            <p class="text-emerald-50/90 text-base md:text-lg font-medium max-w-2xl mx-auto leading-relaxed">
                Unggah dokumen, periksa data hasil baca AI, lengkapi kolom wajib, lalu kirim formulir ke panitia.
                <span class="block text-white/65 text-xs md:text-sm mt-4 font-bold tracking-[0.22em] uppercase">Griya Qur'an & PKBM Tunas Ilmu</span>
            </p>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 -mt-24 relative z-20 pb-20">
        <section class="bg-white rounded-[2rem] shadow-xl shadow-emerald-900/10 border border-emerald-100 p-6 md:p-8 mb-8 animate-fade">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-1">
                    <p class="text-[11px] font-black text-emerald-700 uppercase tracking-widest">Alur Singkat</p>
                    <h2 class="text-2xl font-extrabold text-slate-900 mt-1">Cara Mengisi</h2>
                </div>
                <div class="md:col-span-3 grid sm:grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                        <p class="text-xs font-black text-emerald-700 uppercase">1. Upload</p>
                        <p class="text-sm font-semibold text-slate-700 mt-1">Siapkan Akta, KK, KTP Ayah, KTP Ibu, dan foto anak.</p>
                    </div>
                    <div class="rounded-2xl bg-teal-50 border border-teal-100 p-4">
                        <p class="text-xs font-black text-teal-700 uppercase">2. Cek Data</p>
                        <p class="text-sm font-semibold text-slate-700 mt-1">Periksa ulang hasil baca AI. Koreksi manual bila ada yang tidak sesuai.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs font-black text-slate-600 uppercase">3. Kirim</p>
                        <p class="text-sm font-semibold text-slate-700 mt-1">Tanda tangani pernyataan, lalu kirim formulir ke sistem.</p>
                    </div>
                </div>
            </div>
        </section>

        <form action="/pendaftaran" method="POST" enctype="multipart/form-data" id="form-pendaftaran" class="space-y-10">
            @csrf
            @if($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm font-bold text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif
            
            <!-- LANGKAH 1: UPLOAD -->
            <div class="glass-card rounded-[2rem] shadow-2xl shadow-slate-200/50 overflow-hidden animate-fade border border-white" style="animation-delay: 0.1s">
                <div class="bg-gradient-to-r from-slate-50 to-white p-6 sm:p-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center font-black text-2xl shadow-sm border border-emerald-100">1</div>
                        <div>
                            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Upload Dokumen Wajib</h2>
                            <p class="text-sm text-slate-500 font-medium mt-1">AI membantu membaca data, tetapi orang tua tetap perlu memeriksa hasilnya.</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 lg:p-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- Akta Anak -->
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center relative hover:bg-slate-50 transition hover:border-emerald-400 group" id="dropzone-akta">
                        <label class="cursor-pointer block h-full flex flex-col items-center justify-center min-h-[160px]">
                            <div class="bg-slate-100 text-slate-500 w-12 h-12 rounded-full flex items-center justify-center mb-3 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                            <span class="block font-bold text-slate-700 mb-1">Akta Anak</span>
                            <span class="text-[10px] text-slate-400 block mb-3 font-medium" id="hint-akta">JPG / PNG</span>
                            <input type="file" name="foto_akta_anak" accept="image/*" class="hidden file-input" data-target="akta" required>
                            <img id="preview-akta" class="hidden mx-auto h-28 object-contain rounded-lg mb-2 shadow-sm border border-slate-200">
                            <div id="loading-akta" class="hidden flex-col items-center justify-center py-2">
                                <div class="spinner border-emerald-500 border-t-emerald-200"></div>
                                <span class="text-xs text-emerald-600 mt-2 font-medium" id="status-akta">Menganalisis...</span>
                            </div>
                        </label>
                    </div>

                    <!-- KK -->
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center relative hover:bg-slate-50 transition hover:border-emerald-400 group" id="dropzone-kk">
                        <label class="cursor-pointer block h-full flex flex-col items-center justify-center min-h-[160px]">
                            <div class="bg-slate-100 text-slate-500 w-12 h-12 rounded-full flex items-center justify-center mb-3 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <span class="block font-bold text-slate-700 mb-1">Kartu Keluarga</span>
                            <span class="text-[10px] text-slate-400 block mb-3 font-medium" id="hint-kk">JPG / PNG</span>
                            <input type="file" name="foto_kk" accept="image/*" class="hidden file-input" data-target="kk" required>
                            <img id="preview-kk" class="hidden mx-auto h-28 object-contain rounded-lg mb-2 shadow-sm border border-slate-200">
                            <div id="loading-kk" class="hidden flex-col items-center justify-center py-2">
                                <div class="spinner border-emerald-500 border-t-emerald-200"></div>
                                <span class="text-xs text-emerald-600 mt-2 font-medium" id="status-kk">Menganalisis...</span>
                            </div>
                        </label>
                    </div>

                    <!-- KTP Ayah -->
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center relative hover:bg-slate-50 transition hover:border-emerald-400 group" id="dropzone-ayah">
                        <label class="cursor-pointer block h-full flex flex-col items-center justify-center min-h-[160px]">
                            <div class="bg-slate-100 text-slate-500 w-12 h-12 rounded-full flex items-center justify-center mb-3 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <span class="block font-bold text-slate-700 mb-1">KTP Ayah</span>
                            <span class="text-[10px] text-slate-400 block mb-3 font-medium" id="hint-ayah">JPG / PNG</span>
                            <input type="file" name="foto_ktp_ayah" accept="image/*" class="hidden file-input" data-target="ayah" required>
                            <img id="preview-ayah" class="hidden mx-auto h-28 object-contain rounded-lg mb-2 shadow-sm border border-slate-200">
                            <div id="loading-ayah" class="hidden flex-col items-center justify-center py-2">
                                <div class="spinner border-emerald-500 border-t-emerald-200"></div>
                                <span class="text-xs text-emerald-600 mt-2 font-medium" id="status-ayah">Menganalisis...</span>
                            </div>
                        </label>
                    </div>

                    <!-- KTP Ibu -->
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center relative hover:bg-slate-50 transition hover:border-emerald-400 group" id="dropzone-ibu">
                        <label class="cursor-pointer block h-full flex flex-col items-center justify-center min-h-[160px]">
                            <div class="bg-slate-100 text-slate-500 w-12 h-12 rounded-full flex items-center justify-center mb-3 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <span class="block font-bold text-slate-700 mb-1">KTP Ibu</span>
                            <span class="text-[10px] text-slate-400 block mb-3 font-medium" id="hint-ibu">JPG / PNG</span>
                            <input type="file" name="foto_ktp_ibu" accept="image/*" class="hidden file-input" data-target="ibu" required>
                            <img id="preview-ibu" class="hidden mx-auto h-28 object-contain rounded-lg mb-2 shadow-sm border border-slate-200">
                            <div id="loading-ibu" class="hidden flex-col items-center justify-center py-2">
                                <div class="spinner border-emerald-500 border-t-emerald-200"></div>
                                <span class="text-xs text-emerald-600 mt-2 font-medium" id="status-ibu">Menganalisis...</span>
                            </div>
                        </label>
                    </div>

                    <!-- Pas Foto Siswa -->
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center relative hover:bg-slate-50 transition hover:border-emerald-400 group" id="dropzone-foto">
                        <label class="cursor-pointer block h-full flex flex-col items-center justify-center min-h-[160px]">
                            <div class="bg-slate-100 text-slate-500 w-12 h-12 rounded-full flex items-center justify-center mb-3 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <span class="block font-bold text-slate-700 mb-1">Pas Foto Anak 3x4</span>
                            <span class="text-[10px] text-slate-400 block mb-3 font-medium" id="hint-foto">JPG / PNG</span>
                            <input type="file" name="foto_pas_siswa" accept="image/*" class="hidden file-input" data-target="foto">
                            <img id="preview-foto" class="hidden mx-auto h-28 object-contain rounded-lg mb-2 shadow-sm border border-slate-200">
                            <div id="loading-foto" class="hidden flex-col items-center justify-center py-2">
                                <div class="spinner border-emerald-500 border-t-emerald-200"></div>
                                <span class="text-xs text-emerald-600 mt-2 font-medium" id="status-foto">Mengunggah...</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- LANGKAH 2: FORM DATA -->
            <div class="space-y-6 opacity-40 transition-opacity duration-700 pointer-events-none" id="step-2">
                
                <!-- HEADER STEP 2 -->
                <div class="glass-card rounded-[2rem] shadow-xl px-6 sm:px-8 py-6 flex flex-col md:flex-row items-center justify-between relative overflow-hidden group border border-white">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-emerald-100/50 rounded-full blur-[80px] group-hover:scale-110 transition-transform duration-700"></div>
                    <div class="relative z-10 text-center md:text-left flex flex-col sm:flex-row items-center gap-5 w-full">
                        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center font-black text-2xl shadow-sm border border-emerald-100 flex-shrink-0">2</div>
                        <div class="flex-grow">
                            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Lengkapi Data Peserta</h2>
                            <p class="text-slate-500 mt-1 font-medium text-sm">Sesuai format Dapodik. Kolom <span class="text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">hijau</span> menandakan data otomatis dari AI.</p>
                        </div>
                        <div class="mt-4 sm:mt-0 flex-shrink-0">
                            <span class="inline-flex items-center gap-2 bg-slate-100 text-slate-500 px-5 py-2.5 rounded-xl font-bold border border-slate-200 shadow-sm text-sm" id="status-step-2">
                                <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                Menunggu Dokumen
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- 1. IDENTITAS PESERTA DIDIK -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                        <h3 class="font-bold text-lg text-slate-800">I. Identitas Peserta Didik</h3>
                    </div>
                    <div class="p-6 lg:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div class="md:col-span-2"><label class="form-label required-mark">Nama Lengkap</label><input type="text" name="nama_lengkap" id="f_nama_anak" class="form-input" required></div>
                            <div>
                                <label class="form-label required-mark">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="f_jk" class="form-input" required>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div><label class="form-label required-mark">NIK (Nomor Induk Kependudukan)</label><input type="text" name="nik" id="f_nik_anak" class="form-input" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" required></div>
                            <div><label class="form-label required-mark">Tempat Lahir</label><input type="text" name="tempat_lahir" id="f_tempat_lahir" class="form-input" required></div>
                            <div><label class="form-label required-mark">Tanggal Lahir</label><input type="date" name="tanggal_lahir" id="f_tanggal_lahir" class="form-input" required></div>
                            <div><label class="form-label required-mark">Agama</label><input type="text" name="agama" class="form-input" value="Islam" required></div>
                            <div><label class="form-label">Berkebutuhan Khusus</label><input type="text" name="berkebutuhan_khusus" class="form-input" placeholder="Tidak ada / Sebutkan"></div>
                            <div><label class="form-label required-mark">Hobi</label><input type="text" name="hobi" class="form-input" required></div>
                        </div>

                        <!-- Data Sekolah Asal -->
                        <div class="p-5 bg-slate-50 rounded-xl border border-slate-100">
                            <h4 class="font-semibold text-slate-700 mb-4 text-sm uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                Data Jenjang Sebelumnya (Jika Ada)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="form-label">NISN</label><input type="text" name="nisn" class="form-input"></div>
                                <div><label class="form-label">No. Seri Ijazah</label><input type="text" name="no_seri_ijazah" class="form-input"></div>
                                <div><label class="form-label">No. Seri SKHUN</label><input type="text" name="no_seri_skhun" class="form-input"></div>
                                <div><label class="form-label">No. Ujian Nasional</label><input type="text" name="no_ujian_nasional" class="form-input"></div>
                                <div><label class="form-label required-mark">Nama Sekolah Asal</label><input type="text" name="nama_sekolah_asal" class="form-input" required></div>
                                <div><label class="form-label">NPSN Sekolah Asal</label><input type="text" name="npsn_sekolah_asal" class="form-input"></div>
                                <div class="md:col-span-3"><label class="form-label required-mark">Alamat Sekolah Asal</label><textarea name="alamat_sekolah_asal" rows="1" class="form-input" required></textarea></div>
                            </div>
                        </div>

                        <!-- Alamat Anak -->
                        <div class="p-5 bg-slate-50 rounded-xl border border-slate-100">
                            <h4 class="font-semibold text-slate-700 mb-4 text-sm uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Alamat Tempat Tinggal Anak
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="md:col-span-2 lg:col-span-4"><label class="form-label required-mark">Alamat Jalan</label><textarea name="alamat_lengkap" id="f_alamat" rows="2" class="form-input" required></textarea></div>
                                <div><label class="form-label required-mark">Dusun</label><input type="text" name="dusun" class="form-input" required></div>
                                <div><label class="form-label required-mark">RT / RW</label><input type="text" name="rt_rw" id="f_rt_rw" class="form-input" placeholder="Contoh: 08/02" required></div>
                                <div><label class="form-label required-mark">Kelurahan / Desa</label><input type="text" name="kelurahan_desa" id="f_kelurahan" class="form-input" required></div>
                                <div><label class="form-label required-mark">Kecamatan</label><input type="text" name="kecamatan" id="f_kecamatan" class="form-input" required></div>
                                <div><label class="form-label required-mark">Kabupaten / Kota</label><input type="text" name="kabupaten_kota" id="f_kabupaten" class="form-input" required></div>
                                <div><label class="form-label required-mark">Propinsi</label><input type="text" name="propinsi" class="form-input" required></div>
                                <div><label class="form-label required-mark">Kode Pos</label><input type="text" name="kode_pos" class="form-input" required></div>
                                <div><label class="form-label required-mark">Jenis Tinggal</label><input type="text" name="jenis_tinggal" class="form-input" placeholder="Bersama Orangtua/Wali" required></div>
                                <div><label class="form-label required-mark">Alat Transportasi</label><input type="text" name="alat_transportasi" class="form-input" placeholder="Jalan Kaki/Motor" required></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DATA AYAH KANDUNG -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                        <h3 class="font-bold text-lg text-slate-800">II. Data Ayah Kandung</h3>
                    </div>
                    <div class="p-6 lg:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div><label class="form-label required-mark">NIK Ayah</label><input type="text" name="nik_ayah" id="f_nik_ayah" class="form-input" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" required></div>
                            <div><label class="form-label required-mark">Nama Ayah</label><input type="text" name="nama_ayah" id="f_nama_ayah" class="form-input" required></div>
                            <div><label class="form-label required-mark">No. HP / WA</label><input type="text" name="no_wa_ayah" class="form-input format-wa" inputmode="tel" pattern="(08|628)[0-9]{8,13}" required></div>
                            <div><label class="form-label required-mark">Email Ayah</label><input type="email" name="email_ayah" class="form-input" placeholder="ayah@gmail.com" required></div>
                            <div><label class="form-label required-mark">Tempat Lahir</label><input type="text" name="tempat_lahir_ayah" id="f_tempat_lahir_ayah" class="form-input" required></div>
                            <div><label class="form-label required-mark">Tanggal Lahir</label><input type="date" name="tanggal_lahir_ayah" id="f_tanggal_lahir_ayah" class="form-input" required></div>
                            <div><label class="form-label required-mark">Pekerjaan</label><input type="text" name="pekerjaan_ayah" id="f_pek_ayah" class="form-input" required></div>
                            <div>
                                <label class="form-label required-mark">Pendidikan</label>
                                <select name="pendidikan_ayah" class="form-input" required>
                                    <option value="" disabled selected>Pilih Pendidikan</option>
                                    <option value="SD/Sederajat">SD/Sederajat</option>
                                    <option value="SMP/Sederajat">SMP/Sederajat</option>
                                    <option value="SMA/Sederajat">SMA/Sederajat</option>
                                    <option value="D1">D1</option>
                                    <option value="D2">D2</option>
                                    <option value="D3">D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                    <option value="Tidak bersekolah">Tidak bersekolah</option>
                                </select>
                            </div>
                            <div><label class="form-label required-mark">Penghasilan Bulanan</label><input type="text" name="penghasilan_ayah" class="form-input format-rupiah" placeholder="Contoh: Rp. 3.000.000" required></div>
                            <div><label class="form-label">Berkebutuhan Khusus</label><input type="text" name="berkebutuhan_khusus_ayah" class="form-input" placeholder="Tidak ada"></div>
                        </div>
                        
                        <div class="p-5 bg-slate-50 rounded-xl border border-slate-100">
                            <h4 class="font-semibold text-slate-700 mb-4 text-sm uppercase tracking-wider">Alamat Ayah Sesuai KTP</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="md:col-span-2 lg:col-span-4"><label class="form-label required-mark">Alamat Jalan</label><textarea name="alamat_ayah" id="f_alamat_ayah" rows="1" class="form-input" required></textarea></div>
                                <div><label class="form-label required-mark">RT/RW</label><input type="text" name="rt_rw_ayah" id="f_rt_rw_ayah" class="form-input" required></div>
                                <div><label class="form-label required-mark">Kel/Desa</label><input type="text" name="kelurahan_desa_ayah" id="f_kelurahan_ayah" class="form-input" required></div>
                                <div><label class="form-label required-mark">Kecamatan</label><input type="text" name="kecamatan_ayah" id="f_kecamatan_ayah" class="form-input" required></div>
                            </div>
                        </div>
                        
                        <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-100">
                            <label class="form-label required-mark font-bold text-indigo-900">Apakah Ayah sudah mengikuti Tahsin Khusus Orang Tua/Wali Peserta Didik Ahad Pagi?</label>
                            <select name="status_tahsin_ayah" class="form-input tahsin-select mt-2 max-w-sm" data-target="ayah">
                                <option value="Belum">Belum</option>
                                <option value="Sudah">Sudah</option>
                            </select>
                            <div id="tahsin-pengajar-ayah" class="hidden mt-4 pt-4 border-t border-indigo-200">
                                <label class="form-label text-indigo-800 font-semibold">Sebutkan Nama Pengajar Ustadz/Ustadzah Halaqoh</label>
                                <input type="text" name="pengajar_tahsin_ayah" class="form-input max-w-lg mt-1" placeholder="Nama ustadz...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. DATA IBU KANDUNG -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                        <h3 class="font-bold text-lg text-slate-800">III. Data Ibu Kandung</h3>
                    </div>
                    <div class="p-6 lg:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div><label class="form-label required-mark">NIK Ibu</label><input type="text" name="nik_ibu" id="f_nik_ibu" class="form-input" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" required></div>
                            <div><label class="form-label required-mark">Nama Ibu</label><input type="text" name="nama_ibu" id="f_nama_ibu" class="form-input" required></div>
                            <div><label class="form-label required-mark">No. HP / WA</label><input type="text" name="no_wa_ibu" class="form-input format-wa" inputmode="tel" pattern="(08|628)[0-9]{8,13}" required></div>
                            <div><label class="form-label required-mark">Email Ibu</label><input type="email" name="email_ibu" class="form-input" placeholder="ibu@gmail.com" required></div>
                            <div><label class="form-label required-mark">Tempat Lahir</label><input type="text" name="tempat_lahir_ibu" id="f_tempat_lahir_ibu" class="form-input" required></div>
                            <div><label class="form-label required-mark">Tanggal Lahir</label><input type="date" name="tanggal_lahir_ibu" id="f_tanggal_lahir_ibu" class="form-input" required></div>
                            <div><label class="form-label required-mark">Pekerjaan</label><input type="text" name="pekerjaan_ibu" id="f_pek_ibu" class="form-input" required></div>
                            <div>
                                <label class="form-label required-mark">Pendidikan</label>
                                <select name="pendidikan_ibu" class="form-input" required>
                                    <option value="" disabled selected>Pilih Pendidikan</option>
                                    <option value="SD/Sederajat">SD/Sederajat</option>
                                    <option value="SMP/Sederajat">SMP/Sederajat</option>
                                    <option value="SMA/Sederajat">SMA/Sederajat</option>
                                    <option value="D1">D1</option>
                                    <option value="D2">D2</option>
                                    <option value="D3">D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                    <option value="Tidak bersekolah">Tidak bersekolah</option>
                                </select>
                            </div>
                            <div><label class="form-label required-mark">Penghasilan Bulanan</label><input type="text" name="penghasilan_ibu" class="form-input format-rupiah" placeholder="Contoh: Rp. 3.000.000" required></div>
                            <div><label class="form-label">Berkebutuhan Khusus</label><input type="text" name="berkebutuhan_khusus_ibu" class="form-input" placeholder="Tidak ada"></div>
                        </div>
                        
                        <div class="p-5 bg-slate-50 rounded-xl border border-slate-100">
                            <h4 class="font-semibold text-slate-700 mb-4 text-sm uppercase tracking-wider">Alamat Ibu Sesuai KTP</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="md:col-span-2 lg:col-span-4"><label class="form-label required-mark">Alamat Jalan</label><textarea name="alamat_ibu" id="f_alamat_ibu" rows="1" class="form-input" required></textarea></div>
                                <div><label class="form-label required-mark">RT/RW</label><input type="text" name="rt_rw_ibu" id="f_rt_rw_ibu" class="form-input" required></div>
                                <div><label class="form-label required-mark">Kel/Desa</label><input type="text" name="kelurahan_desa_ibu" id="f_kelurahan_ibu" class="form-input" required></div>
                                <div><label class="form-label required-mark">Kecamatan</label><input type="text" name="kecamatan_ibu" id="f_kecamatan_ibu" class="form-input" required></div>
                            </div>
                        </div>
                        
                        <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-100">
                            <label class="form-label required-mark font-bold text-indigo-900">Apakah Ibu sudah mengikuti Tahsin Khusus Orang Tua/Wali Peserta Didik Ahad Pagi?</label>
                            <select name="status_tahsin_ibu" class="form-input tahsin-select mt-2 max-w-sm" data-target="ibu">
                                <option value="Belum">Belum</option>
                                <option value="Sudah">Sudah</option>
                            </select>
                            <div id="tahsin-pengajar-ibu" class="hidden mt-4 pt-4 border-t border-indigo-200">
                                <label class="form-label text-indigo-800 font-semibold">Sebutkan Nama Pengajar Ustadz/Ustadzah Halaqoh</label>
                                <input type="text" name="pengajar_tahsin_ibu" class="form-input max-w-lg mt-1" placeholder="Nama ustadzah...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. DATA WALI & PERIODIK -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- DATA WALI -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden lg:col-span-2">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                            <h3 class="font-bold text-lg text-slate-800">IV. Data Wali (Opsional)</h3>
                        </div>
                        <div class="p-6 lg:p-8 space-y-6 opacity-80 hover:opacity-100 transition-opacity">
                            <p class="text-xs text-slate-500 mb-2 font-semibold">Hanya diisi jika anak tidak tinggal bersama/diwalikan oleh selain orang tua kandung.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                <div><label class="form-label">NIK Wali</label><input type="text" name="nik_wali" class="form-input"></div>
                                <div><label class="form-label">Nama Wali</label><input type="text" name="nama_wali" class="form-input"></div>
                                                                <div><label class="form-label">No. HP / WA</label><input type="text" name="no_wa_wali" class="form-input format-wa"></div>
                                <div class="md:col-span-2 lg:col-span-1"><label class="form-label">Email Wali</label><input type="email" name="email_wali" class="form-input" placeholder="contoh@gmail.com"></div>
                                <div><label class="form-label">Tempat Lahir</label><input type="text" name="tempat_lahir_wali" class="form-input"></div>
                                <div><label class="form-label">Tanggal Lahir</label><input type="date" name="tanggal_lahir_wali" class="form-input"></div>
                                <div><label class="form-label">Pekerjaan</label><input type="text" name="pekerjaan_wali" class="form-input"></div>
                                <div>
                                    <label class="form-label">Pendidikan</label>
                                    <select name="pendidikan_wali" class="form-input">
                                        <option value="" disabled selected>Pilih Pendidikan</option>
                                        <option value="SD/Sederajat">SD/Sederajat</option>
                                        <option value="SMP/Sederajat">SMP/Sederajat</option>
                                        <option value="SMA/Sederajat">SMA/Sederajat</option>
                                        <option value="D1">D1</option>
                                        <option value="D2">D2</option>
                                        <option value="D3">D3</option>
                                        <option value="S1">S1</option>
                                        <option value="S2">S2</option>
                                        <option value="S3">S3</option>
                                        <option value="Tidak bersekolah">Tidak bersekolah</option>
                                    </select>
                                </div>
                                <div><label class="form-label">Penghasilan Bulanan</label><input type="text" name="penghasilan_wali" class="form-input format-rupiah" placeholder="Contoh: Rp. 3.000.000"></div>
                                <div><label class="form-label">Berkebutuhan Khusus</label><input type="text" name="berkebutuhan_khusus_wali" class="form-input" placeholder="Tidak ada"></div>
                            </div>
                            
                            <div class="p-5 bg-slate-50 rounded-xl border border-slate-100">
                                <h4 class="font-semibold text-slate-700 mb-4 text-sm uppercase tracking-wider">Alamat Wali Sesuai KTP</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="md:col-span-2 lg:col-span-4"><label class="form-label">Alamat Jalan</label><textarea name="alamat_wali" rows="1" class="form-input"></textarea></div>
                                    <div><label class="form-label">RT/RW</label><input type="text" name="rt_rw_wali" class="form-input"></div>
                                    <div><label class="form-label">Kel/Desa</label><input type="text" name="kelurahan_desa_wali" class="form-input"></div>
                                    <div><label class="form-label">Kecamatan</label><input type="text" name="kecamatan_wali" class="form-input"></div>
                                </div>
                            </div>
                            
                            <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-100">
                                <label class="form-label font-bold text-indigo-900">Apakah Wali sudah mengikuti Tahsin Khusus Orang Tua/Wali Peserta Didik Ahad Pagi?</label>
                                <select name="status_tahsin_wali" class="form-input tahsin-select mt-2 max-w-sm" data-target="wali">
                                    <option value="Belum">Belum</option>
                                    <option value="Sudah">Sudah</option>
                                </select>
                                <div id="tahsin-pengajar-wali" class="hidden mt-4 pt-4 border-t border-indigo-200">
                                    <label class="form-label text-indigo-800 font-semibold">Sebutkan Nama Pengajar Ustadz/Ustadzah Halaqoh</label>
                                    <input type="text" name="pengajar_tahsin_wali" class="form-input max-w-lg mt-1" placeholder="Nama ustadz...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATA PERIODIK -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                            <h3 class="font-bold text-lg text-slate-800">V. Data Periodik Peserta Didik</h3>
                        </div>
                        <div class="p-6 lg:p-8 space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="form-label required-mark">Tinggi Badan (cm)</label><input type="number" name="tinggi_badan" class="form-input" required></div>
                                <div><label class="form-label required-mark">Berat Badan (kg)</label><input type="number" name="berat_badan" class="form-input" required></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="form-label required-mark">Jarak ke Sekolah</label><input type="text" name="jarak_ke_sekolah" class="form-input" placeholder="Misal: 2 km" required></div>
                                <div><label class="form-label required-mark">Waktu Tempuh</label><input type="text" name="waktu_tempuh" class="form-input" placeholder="Misal: 15 menit" required></div>
                            </div>
                            <div><label class="form-label required-mark">Jumlah Saudara Kandung</label><input type="number" name="jumlah_saudara_kandung" class="form-input" required></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PERSETUJUAN & TANDA TANGAN -->
            <div class="bg-slate-800 text-white rounded-2xl p-6 lg:p-8 mt-8 shadow-xl">
                <h3 class="font-bold text-xl mb-4 text-emerald-400">Pernyataan Kebenaran Data</h3>
                
                <label class="flex items-start gap-4 cursor-pointer group">
                    <div class="relative flex items-center justify-center mt-1">
                        <input type="checkbox" name="pernyataan_kebenaran_data" id="cb-pernyataan" class="peer appearance-none w-6 h-6 border-2 border-emerald-500 rounded bg-slate-900 checked:bg-emerald-500 transition-colors cursor-pointer" required>
                        <svg class="absolute w-4 h-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div class="text-slate-300 text-sm leading-relaxed">
                        <p class="font-semibold text-white text-base">Yang bertanda tangan di bawah ini Orang Tua/Wali,</p>
                        <p>Dengan ini menyatakan bahwa seluruh data yang saya isikan pada formulir ini adalah benar. Saya bertanggung jawab secara hukum terhadap kebenaran data yang dicantumkan.</p>
                    </div>
                </label>

                <div class="mt-6 pt-6 border-t border-slate-700">
                    <p class="mb-3 font-bold text-indigo-200 required-mark">Nama Terang Penandatangan</p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <select id="ttd-role" class="form-input sm:w-1/3 bg-white text-slate-900 border-indigo-400 font-bold" required>
                            <option value="" disabled selected>Pilih Penandatangan</option>
                            <option value="Bapak">Bapak</option>
                            <option value="Ibu">Ibu</option>
                            <option value="Wali">Wali / Lainnya</option>
                        </select>
                        <input type="text" name="penandatangan_nama" id="ttd-nama" class="form-input flex-1 bg-white text-slate-900 border-indigo-400 font-bold placeholder-slate-400" placeholder="Ketik Nama Lengkap..." required>
                    </div>
                    
                    <div class="mt-5">
                        <p class="mb-2 font-semibold text-slate-300 required-mark">Tanda Tangan Digital</p>
                        <div class="bg-white rounded-lg border-2 border-slate-600 focus-within:border-emerald-500 overflow-hidden relative shadow-inner">
                            <canvas id="signature-pad" class="w-full h-48 touch-none cursor-crosshair"></canvas>
                            <button type="button" id="clear-signature" class="absolute top-2 right-2 bg-slate-100 hover:bg-red-100 text-slate-500 hover:text-red-600 text-xs font-bold px-3 py-1.5 rounded-md transition shadow-sm border border-slate-200">Reset TTD</button>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Gunakan mouse atau sentuhan jari Anda untuk menggambar tanda tangan di atas kotak putih.</p>
                        <input type="hidden" name="tanda_tangan_base64" id="ttd-base64">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6">
                <button type="submit" id="btn-submit" class="w-full bg-slate-200 text-slate-400 py-5 rounded-[1.5rem] font-black text-xl transition-all duration-500 cursor-not-allowed border-2 border-slate-300 shadow-xl overflow-hidden relative group">
                    <span class="relative z-10" id="btn-text">Mohon Upload Dokumen Terlebih Dahulu</span>
                    <div id="btn-bg-gradient" class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-teal-500 opacity-0 transition-opacity duration-500"></div>
                </button>
            </div>
        </form>

        <footer class="mt-20 pb-12 text-center animate-fade" style="animation-delay: 0.5s">
            <button type="button" onclick="showAppInfo()" class="group inline-flex items-center gap-3 p-2 px-6 rounded-2xl bg-white hover:bg-emerald-50 border border-emerald-100 transition-all duration-300 shadow-sm hover:shadow-emerald-100">
                <div class="w-8 h-8 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-700 group-hover:scale-105 transition-transform duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 group-hover:text-emerald-700 transition-colors">Informasi Sistem</span>
            </button>
            <p class="text-slate-400 text-[10px] mt-8 font-bold tracking-widest uppercase opacity-60 italic">&copy; {{ date('Y') }} • Griya Qur'an x Tunas Ilmu</p>
        </footer>
    </div>

    <script>
        function showAppInfo() {
            Swal.fire({
                title: '',
                html: `
                    <div class="text-left">
                        <div class="rounded-[2rem] bg-gradient-to-br from-emerald-700 to-teal-700 p-6 text-white mb-5">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 bg-white/15 rounded-2xl flex items-center justify-center border border-white/20 shrink-0">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-emerald-100 mb-2">Informasi Aplikasi</p>
                                    <h4 class="font-black text-xl leading-tight">Sistem Pendataan Peserta Didik Baru</h4>
                                    <p class="text-sm text-emerald-50/85 mt-2 leading-relaxed">Kelompok Tahfidz Griya Qur'an & PKBM Tunas Ilmu</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 mb-5">
                            <a href="https://wa.me/6285173394373" target="_blank" class="flex items-center gap-3 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 text-emerald-900 hover:bg-emerald-100 transition">
                                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">WhatsApp Kantor</p>
                                    <p class="font-black text-lg tracking-tight">0851 7339 4373</p>
                                </div>
                            </a>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5 text-center">
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-emerald-600/70 mb-2">Developed By</p>
                            <p class="text-slate-800 font-black text-xl tracking-tight">Muhammad Iqbal Putra</p>
                            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-widest">Lead Software Architect</p>
                        </div>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#047857',
                buttonsStyling: true,
                customClass: {
                    popup: 'rounded-[2rem] border-0 shadow-2xl backdrop-blur-xl p-2',
                    confirmButton: 'rounded-2xl px-10 py-4 font-black text-sm uppercase tracking-widest'
                }
            });
        }
        // Threshold ketajaman gambar (semakin tinggi semakin ketat)
        const BLUR_THRESHOLD = 80; 
        const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
        const IMAGE_MAX_DIMENSION = 1800;
        const IMAGE_JPEG_QUALITY = 0.78;

        function formatFileSize(bytes) {
            if (!bytes && bytes !== 0) return '';
            return bytes >= 1024 * 1024
                ? `${(bytes / (1024 * 1024)).toFixed(1)} MB`
                : `${Math.ceil(bytes / 1024)} KB`;
        }

        function replaceInputFile(input, file) {
            if (typeof DataTransfer === 'undefined') return false;

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
            return true;
        }

        function loadImageFromFile(file) {
            return new Promise((resolve, reject) => {
                const url = URL.createObjectURL(file);
                const img = new Image();
                img.onload = () => {
                    URL.revokeObjectURL(url);
                    resolve(img);
                };
                img.onerror = () => {
                    URL.revokeObjectURL(url);
                    reject(new Error('Format gambar tidak dapat dibaca browser.'));
                };
                img.src = url;
            });
        }

        async function compressImageFile(file) {
            if (!file.type.startsWith('image/')) return file;

            const image = await loadImageFromFile(file);
            const scale = Math.min(1, IMAGE_MAX_DIMENSION / Math.max(image.width, image.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.width * scale));
            canvas.height = Math.max(1, Math.round(image.height * scale));

            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise((resolve) => {
                canvas.toBlob(resolve, 'image/jpeg', IMAGE_JPEG_QUALITY);
            });

            if (!blob) return file;

            const compressedName = file.name.replace(/\.[^.]+$/, '') + '.jpg';
            const compressedFile = new File([blob], compressedName, {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });

            return compressedFile.size < file.size ? compressedFile : file;
        }

        // ==========================================
        // FITUR: FORM PERSISTENCE (AUTO-SAVE)
        // ==========================================
        const FORM_STORAGE_KEY = 'spsb_form_data';

        function saveFormData() {
            const formData = {};
            const inputs = document.querySelectorAll('#form-pendaftaran input:not([type="file"]):not([type="hidden"]), #form-pendaftaran select, #form-pendaftaran textarea');
            
            inputs.forEach(input => {
                if (input.name) {
                    if (input.type === 'checkbox') {
                        formData[input.name] = input.checked;
                    } else {
                        formData[input.name] = input.value;
                    }
                }
            });
            localStorage.setItem(FORM_STORAGE_KEY, JSON.stringify(formData));
        }

        function loadFormData() {
            const savedData = localStorage.getItem(FORM_STORAGE_KEY);
            if (!savedData) return;

            try {
                const formData = JSON.parse(savedData);
                Object.keys(formData).forEach(name => {
                    const elements = document.getElementsByName(name);
                    if (elements.length > 0) {
                        const element = elements[0];
                        if (element.type === 'checkbox') {
                            element.checked = formData[name];
                        } else {
                            element.value = formData[name];
                            // Trigger change event for tahsin selects
                            if (element.classList.contains('tahsin-select')) {
                                element.dispatchEvent(new Event('change'));
                            }
                        }
                    }
                });
                
                // Update progress bar after loading
                setTimeout(updateProgress, 500);
            } catch (e) {
                console.error("Gagal memuat data tersimpan", e);
            }
        }

        // Jalankan load data saat halaman siap
        window.addEventListener('DOMContentLoaded', loadFormData);

        // Pasang event listener untuk auto-save pada setiap perubahan
        document.getElementById('form-pendaftaran').addEventListener('input', debounce(saveFormData, 1000));
        document.getElementById('form-pendaftaran').addEventListener('change', saveFormData);

        // Helper: Debounce untuk mencegah penyimpanan terlalu sering saat mengetik
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Fungsi Hitung Blur (Algoritma Laplacian Variance)
        function checkImageBlur(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d', { willReadFrequently: true });
                        const scale = Math.min(1, 400 / Math.max(img.width, img.height));
                        canvas.width = img.width * scale;
                        canvas.height = img.height * scale;
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        
                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const data = imageData.data;
                        const w = canvas.width, h = canvas.height;
                        const gray = new Uint8Array(w * h);
                        
                        for (let i = 0; i < data.length; i += 4) {
                            gray[i/4] = data[i]*0.299 + data[i+1]*0.587 + data[i+2]*0.114;
                        }
                        
                        let sum = 0, sumSq = 0, count = 0;
                        for (let y = 1; y < h - 1; y++) {
                            for (let x = 1; x < w - 1; x++) {
                                const i = y * w + x;
                                const lap = gray[i-w] + gray[i-1] - 4*gray[i] + gray[i+1] + gray[i+w];
                                sum += lap; sumSq += lap*lap; count++;
                            }
                        }
                        const mean = sum / count;
                        resolve((sumSq / count) - (mean * mean));
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        // Setting OCR dari Backend Admin
        const OCR_ENGINE = "{{ $ocr_engine }}";
        const OCR_WEBHOOK_URL = "{{ $ocr_webhook_url }}";

        const DOCUMENT_LABELS = {
            akta: 'Akta Anak',
            kk: 'Kartu Keluarga',
            ayah: 'KTP Ayah',
            ibu: 'KTP Ibu',
            foto: 'Pas Foto Anak',
        };

        const DETECTED_DOCUMENT_LABELS = {
            akta: 'Akta Anak',
            kk: 'Kartu Keluarga',
            ktp: 'KTP',
            foto: 'pas foto',
            unknown: 'dokumen yang belum dikenali',
        };

        function buildDocumentCheckMessage(documentCheck, expectedLabel, detectedLabel) {
            if (documentCheck.expected_gender && documentCheck.detected_gender && documentCheck.gender_matches === false) {
                return `Kolom ini digunakan untuk mengunggah <strong>${expectedLabel}</strong>. Jenis kelamin pada KTP yang terbaca tampaknya <strong>${documentCheck.detected_gender}</strong>.<br><br>Mohon periksa kembali foto yang dipilih, lalu unggah ${expectedLabel} yang jelas dan terbaca.`;
            }

            if (documentCheck.expected_type === 'foto') {
                return `Kolom ini digunakan untuk mengunggah <strong>${expectedLabel}</strong>. Mohon pilih foto wajah anak yang jelas seperti pas foto identitas.`;
            }

            return `Sepertinya dokumen yang dipilih belum sesuai dengan kolom ini.<br><br>Kolom ini digunakan untuk mengunggah <strong>${expectedLabel}</strong>. Mohon periksa kembali foto yang dipilih, lalu unggah foto ${expectedLabel} yang jelas dan terbaca.`;
        }

        async function confirmChildPhotoCheck(documentCheck) {
            if (!documentCheck || documentCheck.is_expected_document !== false) {
                return true;
            }

            const result = await Swal.fire({
                icon: 'info',
                title: 'Mohon periksa kembali foto',
                html: 'Sistem belum dapat memastikan foto ini dengan baik.<br><br>Mohon pastikan foto menampilkan wajah anak dengan cukup jelas. Jika foto yang dipilih sudah benar, Bapak/Ibu tetap dapat melanjutkan.',
                showCancelButton: true,
                confirmButtonText: 'Tetap Gunakan',
                cancelButtonText: 'Ganti Foto',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b'
            });

            return result.isConfirmed;
        }

        async function confirmOcrDocumentCheck(documentCheck, target) {
            if (!documentCheck || documentCheck.is_expected_document !== false) {
                return true;
            }

            if (target === 'foto') {
                return confirmChildPhotoCheck(documentCheck);
            }

            const expectedLabel = DOCUMENT_LABELS[target] || 'dokumen yang sesuai';
            const detectedLabel = DETECTED_DOCUMENT_LABELS[documentCheck.document_type] || 'dokumen lain';
            const confidence = String(documentCheck.confidence || 'low').toLowerCase();

            if (confidence === 'high') {
                await Swal.fire({
                    icon: 'info',
                    title: 'Mohon periksa kembali dokumen',
                    html: buildDocumentCheckMessage(documentCheck, expectedLabel, detectedLabel),
                    confirmButtonText: 'Unggah Ulang',
                    confirmButtonColor: '#10b981'
                });

                return false;
            }

            if (confidence === 'medium') {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Kami belum yakin dokumennya sesuai',
                    html: `Kolom ini digunakan untuk <strong>${expectedLabel}</strong>. Sistem membaca foto ini mungkin sebagai ${detectedLabel}.<br><br>Mohon pastikan foto yang diunggah sudah sesuai dan terlihat jelas.`,
                    showCancelButton: true,
                    confirmButtonText: 'Tetap Gunakan',
                    cancelButtonText: 'Ganti Foto',
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b'
                });

                return result.isConfirmed;
            }

            return true;
        }

        // Fungsi OCR menggunakan Tesseract atau Webhook n8n
        async function runOCR(file, target) {
            if (OCR_ENGINE === 'local') {
                try {
                    const worker = await Tesseract.createWorker('ind');
                    const { data: { text } } = await worker.recognize(file);
                    await worker.terminate();
                    
                    parseAndFill(text, target);
                    return true;
                } catch (err) {
                    console.error("OCR Lokal Failed:", err);
                    return true;
                }
            } else {
                try {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('target', target);
                    
                    // Kita post ke backend Laravel kita sendiri (/upload-ocr)
                    // Nanti Laravel yang akan melempar ke n8n atau Direct AI
                    const response = await fetch('/upload-ocr', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: formData
                    });
                    
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        throw new Error("Server mengembalikan respons tidak valid (mungkin error 500). Kode HTTP: " + response.status);
                    }
                    
                    if (!response.ok) {
                        throw new Error(data.error || "Gagal menghubungi server OCR (HTTP " + response.status + ")");
                    }

                    const canContinue = await confirmOcrDocumentCheck(data.document_check, target);
                    if (!canContinue) {
                        return false;
                    }
                    
                    if (data.fields && Object.keys(data.fields).length > 0) {
                        applyOcrFields(data.fields, target);
                    }

                    if (data.extracted_text) {
                        parseAndFill(data.extracted_text, target);
                    } else {
                        if (target === 'ayah') {
                            if(data.nik) { document.getElementById('f_nik_ayah').value = data.nik; triggerHighlight(document.getElementById('f_nik_ayah')); }
                            if(data.nama) { document.getElementById('f_nama_ayah').value = data.nama; triggerHighlight(document.getElementById('f_nama_ayah')); }
                            if(data.tempat_lahir) { document.getElementById('f_tempat_lahir_ayah').value = data.tempat_lahir; triggerHighlight(document.getElementById('f_tempat_lahir_ayah')); }
                            if(data.tanggal_lahir) { document.getElementById('f_tanggal_lahir_ayah').value = data.tanggal_lahir; triggerHighlight(document.getElementById('f_tanggal_lahir_ayah')); }
                        } else if (target === 'ibu') {
                            if(data.nik) { document.getElementById('f_nik_ibu').value = data.nik; triggerHighlight(document.getElementById('f_nik_ibu')); }
                            if(data.nama) { document.getElementById('f_nama_ibu').value = data.nama; triggerHighlight(document.getElementById('f_nama_ibu')); }
                        } else if (target === 'kk') {
                            if(data.nik) { document.getElementById('f_nik_anak').value = data.nik; triggerHighlight(document.getElementById('f_nik_anak')); }
                        }
                    }
                    return true;
                } catch (err) {
                    console.error("OCR Failed:", err);
                    alert("Terjadi kesalahan saat mengekstrak teks: " + err.message);
                    return true;
                }
            }
        }

        function triggerHighlight(el) {
            el.classList.add('bg-emerald-50', 'border-emerald-500', 'ring-2', 'ring-emerald-200');
            setTimeout(() => {
                el.classList.remove('bg-emerald-50', 'border-emerald-500', 'ring-2', 'ring-emerald-200');
            }, 3000);
        }

        function cleanAiValue(value) {
            return String(value || '')
                .replace(/\b(NIK|NAMA|TEMPAT\s*\/?\s*TGL\s*LAHIR|TEMPAT\s*LAHIR|TANGGAL\s*LAHIR|JENIS\s*KELAMIN|GOL\.?\s*DARAH|ALAMAT|RT\s*\/?\s*RW|KEL\s*\/?\s*DESA|KECAMATAN|AGAMA|PEKERJAAN)\b.*$/i, '')
                .replace(/\s+/g, ' ')
                .replace(/^[\s:;,\.-]+|[\s:;,\.-]+$/g, '')
                .trim();
        }

        function setOcrValue(id, value) {
            const el = document.getElementById(id);
            const clean = cleanAiValue(value);
            if (!el || !clean) return;
            el.value = clean;
            triggerHighlight(el);
        }

        function normalizeAiDate(value) {
            const raw = String(value || '').trim();
            if (!raw) return '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;

            const match = raw.match(/(\d{1,2})[-\s\/\.](\d{1,2})[-\s\/\.](\d{4})/);
            if (!match) return '';

            const day = match[1].padStart(2, '0');
            const month = match[2].padStart(2, '0');
            return `${match[3]}-${month}-${day}`;
        }

        function setOcrDate(id, value) {
            const el = document.getElementById(id);
            const date = normalizeAiDate(value);
            if (!el || !date) return;
            el.value = date;
            triggerHighlight(el);
        }

        function applyOcrFields(fields, target) {
            if (target === 'akta') {
                setOcrValue('f_nama_anak', fields.nama);
                setOcrValue('f_nik_anak', fields.nik);
                setOcrValue('f_tempat_lahir', fields.tempat_lahir);
                setOcrDate('f_tanggal_lahir', fields.tanggal_lahir);

                if (fields.jenis_kelamin) {
                    const gender = cleanAiValue(fields.jenis_kelamin).toUpperCase();
                    const genderEl = document.getElementById('f_jk');
                    if (genderEl) {
                        genderEl.value = gender.includes('PEREMPUAN') ? 'Perempuan' : 'Laki-laki';
                        triggerHighlight(genderEl);
                    }
                }

                setOcrValue('f_nama_ayah', fields.nama_ayah);
                setOcrValue('f_nama_ibu', fields.nama_ibu);
                return;
            }

            if (target === 'ayah') {
                setOcrValue('f_nik_ayah', fields.nik);
                setOcrValue('f_nama_ayah', fields.nama);
                setOcrValue('f_tempat_lahir_ayah', fields.tempat_lahir);
                setOcrDate('f_tanggal_lahir_ayah', fields.tanggal_lahir);
                setOcrValue('f_alamat_ayah', fields.alamat);
                setOcrValue('f_rt_rw_ayah', fields.rt_rw);
                setOcrValue('f_kelurahan_ayah', fields.kelurahan_desa);
                setOcrValue('f_kecamatan_ayah', fields.kecamatan);
                setOcrValue('f_pek_ayah', fields.pekerjaan);
                return;
            }

            if (target === 'ibu') {
                setOcrValue('f_nik_ibu', fields.nik);
                setOcrValue('f_nama_ibu', fields.nama);
                setOcrValue('f_tempat_lahir_ibu', fields.tempat_lahir);
                setOcrDate('f_tanggal_lahir_ibu', fields.tanggal_lahir);
                setOcrValue('f_alamat_ibu', fields.alamat);
                setOcrValue('f_rt_rw_ibu', fields.rt_rw);
                setOcrValue('f_kelurahan_ibu', fields.kelurahan_desa);
                setOcrValue('f_kecamatan_ibu', fields.kecamatan);
                setOcrValue('f_pek_ibu', fields.pekerjaan);
                return;
            }

            if (target === 'kk') {
                setOcrValue('f_alamat', fields.alamat);
                setOcrValue('f_rt_rw', fields.rt_rw);
                setOcrValue('f_kelurahan', fields.kelurahan_desa);
                setOcrValue('f_kecamatan', fields.kecamatan);
            }
        }

        function parseAndFill(text, target) {
            const fullText = text.replace(/\n/g, ' ');
            const noSpaceText = text.replace(/\s+/g, '').toUpperCase();

            let nikMatch = noSpaceText.match(/NIK.*?([0-9OIlZSB]{16})/i);
            if (!nikMatch) nikMatch = noSpaceText.match(/([0-9OIlZSB]{16})/i);

            if (nikMatch) {
                let nikClean = nikMatch[1]
                    .replace(/[O]/g, '0')
                    .replace(/[I|l]/g, '1')
                    .replace(/[Z]/g, '2')
                    .replace(/[S]/g, '5')
                    .replace(/[B]/g, '8');

                if (target === 'akta') {
                    const el = document.getElementById('f_nik_anak');
                    if(el) { el.value = nikClean; triggerHighlight(el); }
                } else if (target === 'ayah') {
                    const el = document.getElementById('f_nik_ayah');
                    if(el) { el.value = nikClean; triggerHighlight(el); }
                } else if (target === 'ibu') {
                    const el = document.getElementById('f_nik_ibu');
                    if(el) { el.value = nikClean; triggerHighlight(el); }
                }
            }

            const ttlMatch = fullText.match(/Lahir\s*[:;]?\s*([A-Za-z\s]+)[\.,\s]+\s*(\d{2}[-\s\./]\d{2}[-\s\./]\d{4})/i);
            if (ttlMatch) {
                let elTmpt = null, elTgl = null;
                if (target === 'ayah') {
                    elTmpt = document.getElementById('f_tempat_lahir_ayah');
                    elTgl = document.getElementById('f_tanggal_lahir_ayah');
                } else if (target === 'ibu') {
                    elTmpt = document.getElementById('f_tempat_lahir_ibu');
                    elTgl = document.getElementById('f_tanggal_lahir_ibu');
                } else if (target === 'akta') {
                    elTmpt = document.getElementById('f_tempat_lahir');
                    elTgl = document.getElementById('f_tanggal_lahir');
                }
                
                if (elTmpt) { elTmpt.value = ttlMatch[1].trim(); triggerHighlight(elTmpt); }
                if (elTgl) {
                    const dateRaw = ttlMatch[2].replace(/[\s\.]/g, '-').replace(/\//g, '-');
                    const parts = dateRaw.split('-');
                    if(parts.length === 3) {
                        elTgl.value = `${parts[2]}-${parts[1]}-${parts[0]}`;
                        triggerHighlight(elTgl);
                    }
                }
            }

            const namaMatch = fullText.match(/NAMA\s*[:;]?\s*([A-Z\s\.,]+)/i);
            if (namaMatch) {
                const nama = cleanAiValue(namaMatch[1]);
                let el = null;
                if (target === 'ayah') el = document.getElementById('f_nama_ayah');
                if (target === 'ibu') el = document.getElementById('f_nama_ibu');
                if (target === 'akta') el = document.getElementById('f_nama_anak');
                if (el) { el.value = nama; triggerHighlight(el); }
            }

            let rtRwRaw = "";
            const rtRwMatch = fullText.match(/RT\s*[\/\\]?\s*RW\s*[:;]?\s*([0-9]{2,3}\s*[\/\\]\s*[0-9]{2,3})/i);
            if (rtRwMatch) {
                rtRwRaw = rtRwMatch[1].replace(/\s+/g, '');
            }

            let pekerjaanRaw = "";
            const pekMatch = fullText.match(/PEKERJAAN\s*[:;]?\s*([A-Z\s\/-]+)/i);
            if (pekMatch) {
                pekerjaanRaw = pekMatch[1].replace(/KEWARGA.*/i, '').replace(/GOL.*/i, '').trim();
            }

            if (target === 'ayah' || target === 'ibu' || target === 'kk') {
                const alamatMatch = fullText.match(/ALAMAT\s*[:;]?\s*([A-Z0-9\s\.\/\-,]+)/i);
                
                let kelurahanRaw = "";
                const kelMatch = fullText.match(/(?:KEL(?:URAHAN)?[\/\\\|\s]+DESA|KEL\s*[\/\\\|]+\s*DESA|KELURAHAN|\bDESA\b)\s*[:;]?\s*([A-Z0-9\s\.\-]+)/i);
                if (kelMatch) {
                    kelurahanRaw = kelMatch[1]
                        .replace(/KECAMATAN.*/i, '')
                        .replace(/KEC\s.*/i, '')
                        .replace(/KEC\..*/i, '')
                        .replace(/AGAMA.*/i, '')
                        .trim();
                }

                let kecamatanRaw = "";
                const kecMatch = fullText.match(/KEC(?:AMATAN|AMALAN|AMAT)?\s*[:;]?\s*([A-Z0-9\s\.\-]+)/i);
                if (kecMatch) {
                    kecamatanRaw = kecMatch[1]
                        .replace(/AGAMA.*/i, '')
                        .replace(/KABUPATEN.*/i, '')
                        .replace(/KOTA.*/i, '')
                        .replace(/STATUS.*/i, '')
                        .trim();
                }
                
                let elAlamat = null, elRtRw = null, elKel = null, elKec = null, elPek = null;
                
                if (target === 'ayah') {
                    elAlamat = document.getElementById('f_alamat_ayah');
                    elRtRw = document.getElementById('f_rt_rw_ayah');
                    elKel = document.getElementById('f_kelurahan_ayah');
                    elKec = document.getElementById('f_kecamatan_ayah');
                    elPek = document.getElementById('f_pek_ayah');
                } else if (target === 'ibu') {
                    elAlamat = document.getElementById('f_alamat_ibu');
                    elRtRw = document.getElementById('f_rt_rw_ibu');
                    elKel = document.getElementById('f_kelurahan_ibu');
                    elKec = document.getElementById('f_kecamatan_ibu');
                    elPek = document.getElementById('f_pek_ibu');
                } else if (target === 'kk') {
                    elAlamat = document.getElementById('f_alamat');
                    elRtRw = document.getElementById('f_rt_rw');
                    elKel = document.getElementById('f_kelurahan');
                    elKec = document.getElementById('f_kecamatan');
                }

                if (alamatMatch && elAlamat) {
                    let alamatRaw = alamatMatch[1]
                        .replace(/RT\s*[\/\\]?\s*RW.*/i, '')
                        .replace(/\bRT\b.*/i, '')
                        .replace(/KEL(?:URAHAN)?[\/\\]DESA.*/i, '')
                        .replace(/KELURAHAN.*/i, '')
                        .replace(/\bDESA\b.*/i, '')
                        .trim();
                    elAlamat.value = alamatRaw;
                    triggerHighlight(elAlamat);
                }

                if (rtRwRaw && elRtRw) {
                    elRtRw.value = rtRwRaw;
                    triggerHighlight(elRtRw);
                }

                if (kelurahanRaw && elKel) {
                    elKel.value = kelurahanRaw;
                    triggerHighlight(elKel);
                }
                
                if (kecamatanRaw && elKec) {
                    elKec.value = kecamatanRaw;
                    triggerHighlight(elKec);
                }

                if (pekerjaanRaw && elPek) {
                    elPek.value = pekerjaanRaw;
                    triggerHighlight(elPek);
                }
            }
        }

        function unlockStep2() {
            const step2 = document.getElementById('step-2');
            if (step2) {
                step2.classList.remove('opacity-40', 'pointer-events-none');
            }
            
            const status = document.getElementById('status-step-2');
            if (status) {
                status.innerText = "Form Siap Diisi";
                status.classList.remove('bg-slate-100', 'text-slate-500', 'border-slate-200');
                status.classList.add('bg-emerald-500', 'text-white', 'border-emerald-400');
            }

            setTimeout(resizeCanvas, 300); // Fix canvas size when wrapper becomes visible
        }

        let validFilesCount = 0;

        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', async function() {
                let file = this.files[0];
                const target = this.dataset.target;
                const dropzone = document.getElementById('dropzone-' + target);
                const preview = document.getElementById('preview-' + target);
                const pdfIcon = document.getElementById('pdf-icon-' + target);
                const loading = document.getElementById('loading-' + target);
                const status = document.getElementById('status-' + target);
                const hint = document.getElementById('hint-' + target);
                
                if (!file) return;

                const isPdf = file.type === 'application/pdf';

                // Reset UI for this box
                preview.classList.add('hidden');
                if (pdfIcon) pdfIcon.classList.add('hidden');
                hint.classList.add('hidden');
                loading.classList.remove('hidden');
                loading.classList.add('flex');
                dropzone.classList.remove('border-red-500', 'bg-red-50', 'border-emerald-500', 'bg-emerald-50', 'border-slate-300');
                dropzone.classList.add('border-blue-400', 'bg-blue-50');

                if (isPdf && file.size > MAX_UPLOAD_BYTES) {
                    loading.classList.remove('flex');
                    loading.classList.add('hidden');
                    hint.classList.remove('hidden');
                    dropzone.classList.replace('border-blue-400', 'border-red-500');
                    dropzone.classList.replace('bg-blue-50', 'bg-red-50');

                    Swal.fire({
                        icon: 'error',
                        title: 'File Terlalu Besar',
                        text: `Ukuran PDF ${formatFileSize(file.size)}. Maksimal 10 MB per berkas. Mohon kompres PDF atau foto ulang dokumen dalam format gambar.`,
                        confirmButtonText: 'Mengerti',
                        confirmButtonColor: '#10b981'
                    });

                    this.value = '';
                    return;
                }
                
                // 1. Cek Blur (Hanya untuk Gambar)
                if (!isPdf) {
                    status.innerText = "Mengoptimalkan foto...";
                    try {
                        const compressedFile = await compressImageFile(file);
                        if (compressedFile !== file && replaceInputFile(this, compressedFile)) {
                            file = compressedFile;
                        }
                    } catch (err) {
                        console.warn('Kompresi gambar dilewati:', err);
                    }

                    if (file.size > MAX_UPLOAD_BYTES) {
                        loading.classList.remove('flex');
                        loading.classList.add('hidden');
                        hint.classList.remove('hidden');
                        dropzone.classList.replace('border-blue-400', 'border-red-500');
                        dropzone.classList.replace('bg-blue-50', 'bg-red-50');

                        Swal.fire({
                            icon: 'error',
                            title: 'Foto Terlalu Besar',
                            text: `Ukuran foto ${formatFileSize(file.size)}. Maksimal 10 MB per berkas. Mohon foto ulang dengan mode kamera biasa, bukan mode 50MP/HD.`,
                            confirmButtonText: 'Coba Lagi',
                            confirmButtonColor: '#10b981'
                        });

                        this.value = '';
                        return;
                    }

                    status.innerText = "Mengecek resolusi...";
                    const variance = await checkImageBlur(file);
                    
                    if (variance < BLUR_THRESHOLD) {
                        loading.classList.remove('flex');
                        loading.classList.add('hidden');
                        hint.classList.remove('hidden');
                        dropzone.classList.replace('border-blue-400', 'border-red-500');
                        dropzone.classList.replace('bg-blue-50', 'bg-red-50');
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Foto Terlalu Buram!',
                            text: `Sistem mendeteksi foto ${target.toUpperCase()} kurang fokus. Mohon foto ulang dengan cahaya yang terang agar AI bisa membacanya dengan akurat.`,
                            confirmButtonText: 'Coba Lagi',
                            confirmButtonColor: '#10b981'
                        });
                        
                        this.value = '';
                        return;
                    }

                    // Tampilkan Preview Gambar
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                        dropzone.classList.replace('border-blue-400', 'border-emerald-500');
                        dropzone.classList.replace('bg-blue-50', 'bg-white');
                    }
                    reader.readAsDataURL(file);
                } else {
                    // Tampilkan Ikon PDF
                    if (pdfIcon) pdfIcon.classList.remove('hidden');
                    dropzone.classList.replace('border-blue-400', 'border-emerald-500');
                    dropzone.classList.replace('bg-blue-50', 'bg-white');
                    status.innerText = "PDF siap diunggah";
                }

                // 2. Jalankan OCR/validasi AI hanya untuk gambar. Foto anak dicek jenis fotonya tanpa mengisi data.
                if (!isPdf) {
                    status.innerText = target === 'foto' ? "AI sedang memeriksa foto..." : "AI sedang membaca...";
                    const canUseFile = await runOCR(file, target);
                    if (!canUseFile) {
                        this.value = '';
                        preview.classList.add('hidden');
                        hint.classList.remove('hidden');
                        loading.classList.remove('flex');
                        loading.classList.add('hidden');
                        dropzone.classList.remove('border-emerald-500', 'bg-white');
                        dropzone.classList.add('border-red-500', 'bg-red-50');
                        return;
                    }
                }
                
                loading.classList.remove('flex');
                loading.classList.add('hidden');
                
                validFilesCount++;
                if(validFilesCount >= 1) {
                    unlockStep2();
                }
            });
        });

        // Script Canvas Tanda Tangan
        const canvas = document.getElementById('signature-pad');
        function resizeCanvas() {
            const ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        window.addEventListener("resize", resizeCanvas);
        // Call it immediately but also ensure it resizes after step 2 is unlocked
        setTimeout(resizeCanvas, 100);

        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(15, 23, 42)' // slate-900
        });

        document.getElementById('clear-signature').addEventListener('click', function () {
            signaturePad.clear();
            // Force redraw logic to prevent blurry lines on mobile
            resizeCanvas();
            setTimeout(updateProgress, 100);
        });

        // Tangkap event submit untuk konfirmasi ganda
        document.getElementById('form-pendaftaran').addEventListener('submit', function(e) {
            e.preventDefault();

            // Check for empty required fields
            const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
            const emptyFields = [];

            requiredInputs.forEach(input => {
                let isEmpty = false;
                if (input.type === 'checkbox') {
                    if (!input.checked) isEmpty = true;
                } else if (input.type === 'file') {
                    if (!input.files || input.files.length === 0) isEmpty = true;
                } else {
                    if (input.value.trim() === '') isEmpty = true;
                }

                if (isEmpty) {
                    let sectionName = "Formulir";
                    const card = input.closest('.glass-card, .bg-white, .bg-slate-800');
                    if (card) {
                        const heading = card.querySelector('h2, h3');
                        if (heading) {
                            sectionName = heading.textContent.replace(/^\s+|\s+$/g, '').replace(/\s+/g, ' ');
                        }
                    }
                    
                    let labelText = "";
                    if (input.type === 'file') {
                        if (input.name === 'foto_akta_anak') labelText = 'Dokumen Akta Anak';
                        else if (input.name === 'foto_kk') labelText = 'Dokumen Kartu Keluarga';
                        else if (input.name === 'foto_ktp_ayah') labelText = 'Dokumen KTP Ayah';
                        else if (input.name === 'foto_ktp_ibu') labelText = 'Dokumen KTP Ibu';
                        else if (input.name === 'foto_pas_siswa') labelText = 'Pas Foto Anak 3x4';
                        else labelText = 'Dokumen Pendukung';
                    } else {
                        const labelEl = input.closest('div')?.querySelector('label, .form-label');
                        if (labelEl) {
                            labelText = labelEl.textContent.replace('*', '').trim();
                        } else {
                            labelText = input.placeholder || input.name || "Kolom Wajib";
                        }
                    }

                    emptyFields.push({
                        element: input,
                        section: sectionName,
                        label: labelText
                    });
                }
            });

            const isSignatureEmpty = typeof signaturePad === 'undefined' || signaturePad.isEmpty();

            if (emptyFields.length > 0 || isSignatureEmpty) {
                // Group empty fields by section
                const grouped = {};
                emptyFields.forEach(field => {
                    if (!grouped[field.section]) {
                        grouped[field.section] = [];
                    }
                    grouped[field.section].push(field);
                });

                if (isSignatureEmpty) {
                    const secName = "Pernyataan Kebenaran Data";
                    if (!grouped[secName]) grouped[secName] = [];
                    grouped[secName].push({
                        label: "Tanda Tangan Digital"
                    });
                }

                // Show SweetAlert with categories
                let htmlContent = `
                    <div class="text-left text-sm text-slate-600 max-h-60 overflow-y-auto pr-2">
                        <p class="mb-4 text-slate-500 font-medium">Mohon lengkapi bagian-bagian wajib berikut sebelum mengirimkan formulir:</p>
                `;

                for (const section in grouped) {
                    htmlContent += `
                        <div class="mb-4">
                            <h5 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <span class="w-1.5 h-3 bg-rose-500 rounded-full"></span> ${section}
                            </h5>
                            <ul class="list-disc pl-5 space-y-1 text-xs text-rose-600 font-semibold">
                    `;
                    grouped[section].forEach(field => {
                        htmlContent += `<li>${field.label}</li>`;
                    });
                    htmlContent += `
                            </ul>
                        </div>
                    `;
                }

                htmlContent += `</div>`;

                Swal.fire({
                    icon: 'warning',
                    title: '<span class="text-lg font-black text-slate-800 uppercase tracking-tight">Formulir Belum Lengkap</span>',
                    html: htmlContent,
                    confirmButtonText: 'Lengkapi Sekarang',
                    confirmButtonColor: '#ef4444',
                    customClass: {
                        popup: 'rounded-[2rem] border-0 shadow-2xl',
                        confirmButton: 'rounded-xl px-6 py-3 font-bold text-sm uppercase tracking-wider'
                    }
                });
                return;
            }

            // If completely valid, proceed with double confirmation
            Swal.fire({
                title: 'Konfirmasi Data',
                html: `
                    <div class="text-left text-sm mb-4 text-slate-600">
                        Pastikan Anda telah mengecek ulang seluruh isian. AI hanya membantu menyalin data, namun <b>Anda wajib memastikan tidak ada data yang salah ketik/kurang pas</b>.
                    </div>
                    <label class="flex items-start gap-3 text-left bg-emerald-50 p-4 rounded-xl border border-emerald-200 cursor-pointer">
                        <input type="checkbox" id="swal-check" class="mt-1 w-5 h-5 text-emerald-600 rounded border-emerald-300 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-emerald-900 leading-snug">Saya menjamin bahwa seluruh data yang saya isikan sudah saya periksa dan sudah sesuai/benar.</span>
                    </label>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim Sekarang',
                cancelButtonText: 'Cek Ulang Data',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const isChecked = document.getElementById('swal-check').checked;
                    if (!isChecked) {
                        Swal.showValidationMessage('Anda harus mencentang kotak konfirmasi terlebih dahulu!');
                        return false;
                    }
                    return true;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Mengirim Data...',
                        html: 'Sistem sedang memproses formulir dan meracik dokumen Anda.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    document.getElementById('ttd-base64').value = signaturePad.toDataURL('image/png');
                    localStorage.removeItem(FORM_STORAGE_KEY);
                    document.getElementById('form-pendaftaran').submit();
                }
            });
        });

        // Script Tanda Tangan Role
        document.getElementById('ttd-role').addEventListener('change', function() {
            const role = this.value;
            const ttdNamaInput = document.getElementById('ttd-nama');
            if (role === 'Bapak') {
                ttdNamaInput.value = document.getElementById('f_nama_ayah').value;
            } else if (role === 'Ibu') {
                ttdNamaInput.value = document.getElementById('f_nama_ibu').value;
            } else {
                ttdNamaInput.value = document.querySelector('input[name="nama_wali"]').value || '';
            }
            ttdNamaInput.focus();
        });

        // Script Tahsin
        document.querySelectorAll('.tahsin-select').forEach(select => {
            select.addEventListener('change', function() {
                const target = this.dataset.target;
                const pengajarDiv = document.getElementById('tahsin-pengajar-' + target);
                if(this.value === 'Sudah') {
                    pengajarDiv.classList.remove('hidden');
                } else {
                    pengajarDiv.classList.add('hidden');
                    pengajarDiv.querySelector('input').value = '';
                }
            });
        });

        // Script Format Rupiah
        document.querySelectorAll('.format-rupiah').forEach(input => {
            input.addEventListener('keyup', function(e) {
                let val = this.value.replace(/[^,\d]/g, '').toString();
                if (val) {
                    let split = val.split(',');
                    let sisa = split[0].length % 3;
                    let rupiah = split[0].substr(0, sisa);
                    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
                    if (ribuan) {
                        let separator = sisa ? '.' : '';
                        rupiah += separator + ribuan.join('.');
                    }
                    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
                    this.value = 'Rp. ' + rupiah;
                } else {
                    this.value = '';
                }
            });
        });

        // Script Format Nomor WA
        document.querySelectorAll('.format-wa').forEach(input => {
            input.addEventListener('input', function() {
                let val = this.value.replace(/\D/g, ''); // hanya angka
                if (val.length > 0) {
                    if (val.startsWith('8')) {
                        val = '0' + val;
                    } else if (val.startsWith('628')) {
                        val = '08' + val.substring(3);
                    } else if (val.startsWith('0') && !val.startsWith('08') && val.length > 1) {
                        // jika mulai 0 tapi bukan 08, ubah paksa ke 08 (asumsi typo)
                        val = '08' + val.substring(2);
                    } else if (!val.startsWith('0') && !val.startsWith('8') && !val.startsWith('6')) {
                        val = '08' + val;
                    }
                }
                this.value = val;
            });
        });
    
        // LOGIKA PROGRESS BAR & TOMBOL SUBMIT
        function updateProgress() {
            const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
            let filledCount = 0;
            let totalCount = requiredInputs.length;
            
            // Tanda tangan manual check
            totalCount += 1;
            if (typeof signaturePad !== 'undefined' && !signaturePad.isEmpty()) {
                filledCount += 1;
            }

            requiredInputs.forEach(input => {
                if (input.type === 'checkbox') {
                    if (input.checked) filledCount++;
                } else if (input.type === 'file') {
                    if (input.files && input.files.length > 0) filledCount++;
                } else {
                    if (input.value.trim() !== '') filledCount++;
                }
            });

            const percentage = Math.round((filledCount / totalCount) * 100);
            
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');

            if (progressContainer && (filledCount > 0 || percentage > 0)) {
                progressContainer.classList.remove('-translate-y-full');
            }

            if (progressBar) progressBar.style.width = percentage + '%';
            if (progressText) progressText.innerText = percentage + '%';

            // Update Submit Button States dynamically
            const btn = document.getElementById('btn-submit');
            const btnText = document.getElementById('btn-text');
            const btnBg = document.getElementById('btn-bg-gradient');

            if (percentage === 100) {
                if (btn) {
                    btn.classList.remove('bg-slate-200', 'text-slate-400', 'border-slate-300', 'cursor-not-allowed');
                    btn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-700', 'hover:bg-emerald-700', 'shadow-emerald-500/30');
                }
                if (btnText) btnText.innerText = "Kirim Pendataan Sekarang";
                if (btnBg) {
                    btnBg.classList.remove('opacity-0');
                    btnBg.classList.add('opacity-100');
                }
            } else {
                if (btn) {
                    btn.classList.add('bg-slate-200', 'text-slate-400', 'border-slate-300', 'cursor-not-allowed');
                    btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-700', 'hover:bg-emerald-700', 'shadow-emerald-500/30');
                }
                if (btnText) btnText.innerText = `Formulir Belum Lengkap (${percentage}%)`;
                if (btnBg) {
                    btnBg.classList.remove('opacity-100');
                    btnBg.classList.add('opacity-0');
                }
            }
        }

        // Listener untuk text/select
        document.addEventListener('input', function(e) {
            if (e.target.matches('input, select, textarea')) updateProgress();
        });
        document.addEventListener('change', function(e) {
            if (e.target.matches('input, select, textarea')) updateProgress();
        });

        // Listener manual untuk signature pad
        const sigCanvas = document.getElementById('signature-pad');
        if (sigCanvas) {
            sigCanvas.addEventListener('mouseup', updateProgress);
            sigCanvas.addEventListener('touchend', updateProgress);
            sigCanvas.addEventListener('mouseleave', updateProgress);
        }
        
        // Cek inisial saat halaman selesai load
        window.addEventListener('load', updateProgress);
    </script>
</body>
</html>


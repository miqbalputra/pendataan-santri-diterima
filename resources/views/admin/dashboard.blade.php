<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SPSB</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
    <!-- Navbar -->
    <nav class="bg-emerald-700 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="font-bold text-xl flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Admin SPSB
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex flex-col items-end">
                    <span class="text-emerald-100 text-[10px] font-bold uppercase tracking-widest">Admin Aktif</span>
                    <span class="font-bold text-sm">{{ auth()->user()->username }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-emerald-800 hover:bg-rose-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-inner">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>
    <div class="bg-white border-b shadow-sm sticky top-0 z-20">
        <div class="max-w-7xl mx-auto flex overflow-x-auto">
            <button onclick="switchTab('dashboard')" id="tab-dashboard" class="tab-btn px-6 py-4 font-bold border-b-2 text-emerald-600 border-emerald-600 whitespace-nowrap">Dashboard</button>
            <button onclick="switchTab('periode')" id="tab-periode" class="tab-btn px-6 py-4 font-bold border-b-2 text-slate-500 border-transparent hover:text-slate-800 whitespace-nowrap">Periode</button>
            <button onclick="switchTab('laporan')" id="tab-laporan" class="tab-btn px-6 py-4 font-bold border-b-2 text-slate-500 border-transparent hover:text-slate-800 whitespace-nowrap">Laporan</button>
            <button onclick="switchTab('log')" id="tab-log" class="tab-btn px-6 py-4 font-bold border-b-2 text-slate-500 border-transparent hover:text-slate-800 whitespace-nowrap">Log Aktivitas</button>
            <button onclick="switchTab('pengaturan')" id="tab-pengaturan" class="tab-btn px-6 py-4 font-bold border-b-2 text-slate-500 border-transparent hover:text-slate-800 whitespace-nowrap">Pengaturan</button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto p-4 sm:p-8">
<div id="content-dashboard" class="tab-content block">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow border-l-4 border-blue-500 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 font-bold uppercase">Total Pendaftar</p>
                    <p class="text-3xl font-black text-slate-800">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow border-l-4 border-amber-500 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 font-bold uppercase">Menunggu Review</p>
                    <p class="text-3xl font-black text-slate-800">{{ $stats['pending'] }}</p>
                </div>
                <div class="bg-amber-100 p-3 rounded-full text-amber-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow border-l-4 border-emerald-500 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 font-bold uppercase">Telah Diterima</p>
                    <p class="text-3xl font-black text-slate-800">{{ $stats['diterima'] }}</p>
                </div>
                <div class="bg-emerald-100 p-3 rounded-full text-emerald-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Tabel Pendaftar -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="p-6 border-b bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Data Pendaftar Terbaru</h2>
                    <p class="text-xs text-slate-500 mt-1 font-medium italic">Menampilkan daftar Peserta Didik yang masuk ke sistem secara real-time.</p>
                </div>
                <form action="" method="GET" class="flex gap-2 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" name="search" placeholder="Cari nama / WA..." class="border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm w-full focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 outline-none transition" value="{{ request('search') }}">
                    </div>
                    <button type="submit" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-800 transition shadow-lg shadow-slate-900/20">Cari</button>
                </form>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b text-slate-500 uppercase tracking-wider font-bold text-[11px]">
                        <tr>
                            <th class="p-4 w-12 text-center">No.</th>
                            <th class="p-4">Calon Peserta Didik</th>
                            <th class="p-4 text-center">L/P</th>
                            <th class="p-4">Data Orang Tua</th>
                            <th class="p-4 text-center">WhatsApp</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pendaftar as $p)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-4 text-center font-bold text-slate-400">{{ ($pendaftar->currentPage() - 1) * $pendaftar->perPage() + $loop->iteration }}.</td>
                            <td class="p-4">
                                <div class="font-extrabold text-slate-900">{{ $p->nama_lengkap }}</div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">{{ $p->nik_anak ?? 'NIK Tidak Ada' }}</div>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2 py-1 rounded-lg font-bold text-[11px] {{ $p->jenis_kelamin == 'Laki-laki' ? 'bg-blue-50 text-blue-600' : 'bg-pink-50 text-pink-600' }}">
                                    {{ $p->jenis_kelamin == 'Laki-laki' ? 'L' : 'P' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-4 h-4 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[9px] font-bold">A</span>
                                        <span class="text-slate-700 font-bold text-xs">{{ $p->nama_ayah }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-4 h-4 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center text-[9px] font-bold">I</span>
                                        <span class="text-slate-700 font-bold text-xs">{{ $p->nama_ibu }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-center gap-3">
                                    @if($p->no_wa_ayah)
                                    <a href="https://wa.me/{{ preg_replace('/^08/', '628', $p->no_wa_ayah) }}" target="_blank" title="Chat Ayah: {{ $p->no_wa_ayah }}" class="w-9 h-9 flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition shadow-sm border border-emerald-100">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                    </a>
                                    @endif
                                    @if($p->no_wa_ibu)
                                    <a href="https://wa.me/{{ preg_replace('/^08/', '628', $p->no_wa_ibu) }}" target="_blank" title="Chat Ibu: {{ $p->no_wa_ibu }}" class="w-9 h-9 flex items-center justify-center rounded-xl bg-pink-50 text-pink-600 hover:bg-pink-600 hover:text-white transition shadow-sm border border-pink-100">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                    </a>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                @if($p->status_pendaftaran == 'Diterima')
                                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-full text-[10px] font-black tracking-wider uppercase">DITERIMA</span>
                                @elseif($p->status_pendaftaran == 'Ditolak')
                                    <span class="bg-rose-100 text-rose-700 px-3 py-1.5 rounded-full text-[10px] font-black tracking-wider uppercase">DITOLAK</span>
                                @else
                                    <span class="bg-amber-100 text-amber-700 px-3 py-1.5 rounded-full text-[10px] font-black tracking-wider uppercase">MENUNGGU</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.show', $p->id) }}" class="bg-slate-800 text-white hover:bg-slate-700 px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm">Detail</a>
                                    <a href="{{ route('admin.edit', $p->id) }}" class="bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm">Edit</a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="bg-slate-100 p-4 rounded-full mb-4">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    </div>
                                    <p class="text-slate-500 font-bold">Belum ada data pendaftar.</p>
                                    <p class="text-xs text-slate-400 mt-1 italic">Silakan periksa kembali filter pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            @if($pendaftar->hasPages())
            <div class="p-6 border-t bg-slate-50/50 flex justify-center">
                {{ $pendaftar->links('pagination::tailwind') }}
            </div>
            @endif
        </div>

        <!-- Pengaturan Sistem -->
        </div> <!-- End before settings -->
<div id="content-pengaturan" class="tab-content hidden">
<h2 class="text-xl font-bold mb-4">Pengaturan Sistem Dasar</h2>
        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-6 rounded-lg font-medium border border-green-200">{{ session('success') }}</div>
        @endif
        <div class="bg-white rounded-xl shadow p-6 max-w-3xl border-t-4 border-slate-800">
            <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                    <label class="block font-bold text-slate-800 text-lg mb-2">Pilih Mode Ekstraksi AI (OCR Engine)</label>
                    <select name="ocr_engine" id="ocr_engine" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500 bg-white p-3 font-medium text-slate-700" onchange="toggleSettings()">
                        <option value="local" {{ $ocr_engine == 'local' ? 'selected' : '' }}>Mode 1: Lokal Tesseract (Akurat: Rendah)</option>
                        <option value="n8n" {{ $ocr_engine == 'n8n' ? 'selected' : '' }}>Mode 2: Cloud n8n Webhook (Akurat: Tinggi, via n8n workflow)</option>
                        <option value="direct_ai" {{ $ocr_engine == 'direct_ai' ? 'selected' : '' }}>Mode 3: Direct API AI / LLM (Akurat: Sangat Tinggi, Bypass n8n)</option>
                    </select>
                </div>

                <!-- Custom Kop Surat -->
                <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-200 space-y-4">
                    <h3 class="font-bold text-indigo-800 text-lg flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg> Kustomisasi Kop Surat (PDF)</h3>
                    <p class="text-sm text-indigo-600 mb-2">Teks ini akan muncul di bagian paling atas pada cetakan formulir pendaftaran PDF.</p>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase mb-1">Baris 1 (Nama Lembaga / Judul Utama)</label>
                            <input type="text" name="kop_baris_1" class="w-full border-slate-300 rounded-lg p-2.5 font-bold" value="{{ $kop_baris_1 }}">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase mb-1">Baris 2 (Sub-Judul / Deskripsi)</label>
                            <input type="text" name="kop_baris_2" class="w-full border-slate-300 rounded-lg p-2.5" value="{{ $kop_baris_2 }}">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase mb-1">Baris 3 (Alamat / Kontak)</label>
                            <input type="text" name="kop_baris_3" class="w-full border-slate-300 rounded-lg p-2.5 text-xs" value="{{ $kop_baris_3 }}">
                        </div>
                    </div>
                </div>

                                <!-- Fitur Kunci Aplikasi -->
                <div class="bg-rose-50 p-5 rounded-xl border border-rose-200">
                    <h3 class="font-bold text-rose-800 text-lg flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg> Kunci / Tutup Pendaftaran</h3>
                    <p class="text-sm text-rose-600 mb-4">Jika diaktifkan, halaman pendaftaran akan ditutup dan pengunjung tidak bisa mengisi formulir. (Untuk menghemat kuota LLM saat pendaftaran usai).</p>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="app_locked" value="1" {{ $app_locked ? 'checked' : '' }} class="w-6 h-6 text-rose-600 rounded">
                        <span class="font-bold text-rose-900">Aplikasi Ditutup (Locked)</span>
                    </label>
                </div>

                <!-- Setting n8n -->
                <div id="setting_n8n" class="p-5 border border-blue-200 bg-blue-50 rounded-xl space-y-4 transition-all {{ $ocr_engine == 'n8n' ? 'block' : 'hidden' }}">
                    <h3 class="font-bold text-blue-800 flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Konfigurasi Webhook n8n</h3>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-sm">Webhook URL n8n (Untuk OCR)</label>
                        <input type="text" name="ocr_webhook_url" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 p-3" value="{{ $ocr_webhook_url }}" placeholder="https://n8n.domain.com/webhook/ocr-ktp">
                        <p class="text-xs text-slate-500 mt-1">Masukkan URL dari Node Webhook n8n Anda. Aplikasi akan melempar file gambar ke URL ini.</p>
                    </div>
                </div>

                <!-- Setting Direct AI -->
                <div id="setting_ai" class="p-5 border border-purple-200 bg-purple-50 rounded-xl space-y-4 transition-all {{ $ocr_engine == 'direct_ai' ? 'block' : 'hidden' }}">
                    <h3 class="font-bold text-purple-800 flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg> Konfigurasi Direct API (OpenAI/Gemini/dll)</h3>
                    <p class="text-xs text-purple-600 mb-2">Aplikasi akan menembak API AI secara langsung untuk mengekstrak KTP/KK tanpa perlu melewati n8n.</p>
                    
                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-sm">Endpoint API URL</label>
                        <input type="text" id="ai_endpoint" name="ai_endpoint" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2" value="{{ $ai_endpoint }}" placeholder="https://api.openai.com/v1/chat/completions">
                        <p class="text-[11px] text-slate-500 mt-1">Standar OpenAI: https://api.openai.com/v1/chat/completions</p>
                    </div>
                    
                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-sm">API Key (Bearer Token)</label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="password" id="ai_api_key" name="ai_api_key" class="flex-1 border-slate-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2" value="{{ $ai_api_key }}" placeholder="sk-proj-xxxxxxx...">
                            <button type="button" id="btn-test-ai" onclick="testConnection()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg shadow whitespace-nowrap transition flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Tes Koneksi
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-sm">Pilih Model AI (Vision Support)</label>
                        <select id="ai_model" name="ai_model" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 bg-white">
                            <option value="{{ $ai_model }}">{{ $ai_model }} (Aktif / Default)</option>
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1" id="model-hint">Klik tombol "Tes Koneksi" di atas untuk mengambil daftar model yang tersedia.</p>
                    </div>
                </div>

                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-8 py-3 rounded-lg font-bold w-full sm:w-auto transition shadow-lg shadow-slate-800/30">Simpan Pengaturan Utama</button>
            </form>
        </div>

        <h2 class="text-xl font-bold mt-12 mb-4 text-rose-800 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            Pengamanan Akun Admin
        </h2>
        <div class="bg-white rounded-xl shadow p-6 max-w-3xl border-t-4 border-rose-600">
            <form action="{{ route('admin.account.update') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-sm">Username Admin</label>
                        <input type="text" name="username" class="w-full border-slate-300 rounded-lg p-2.5 font-bold bg-slate-50" value="{{ auth()->user()->username }}" required>
                        <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-tight">Digunakan untuk login</p>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-sm">Password Baru (Opsional)</label>
                        <input type="password" name="password" class="w-full border-slate-300 rounded-lg p-2.5 bg-slate-50" placeholder="••••••••">
                        <p class="text-[10px] text-rose-500 mt-1 uppercase tracking-tight font-bold italic">Kosongkan jika tidak diubah</p>
                    </div>
                </div>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-8 py-3 rounded-lg font-bold w-full sm:w-auto transition shadow-lg shadow-rose-600/30">Update Kredensial Login</button>
            </form>
        </div>

        <script>
            function switchTab(tabId) {
                // Hide all contents
                document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
                // Reset all tab buttons
                document.querySelectorAll('.tab-btn').forEach(el => {
                    el.classList.remove('text-emerald-600', 'border-emerald-600');
                    el.classList.add('text-slate-500', 'border-transparent');
                });
                
                // Show selected content
                document.getElementById('content-' + tabId).classList.remove('hidden');
                // Highlight selected tab
                let btn = document.getElementById('tab-' + tabId);
                btn.classList.add('text-emerald-600', 'border-emerald-600');
                btn.classList.remove('text-slate-500', 'border-transparent');
            }
                        async function testConnection() {
                const endpoint = document.getElementById('ai_endpoint').value;
                const apiKey = document.getElementById('ai_api_key').value;
                const btn = document.getElementById('btn-test-ai');

                if (!endpoint || !apiKey) {
                    Swal.fire('Error', 'Harap isi Endpoint URL dan API Key terlebih dahulu!', 'warning');
                    return;
                }

                btn.innerHTML = '<span class="spinner border-white border-t-purple-300 w-4 h-4 rounded-full border-2 animate-spin inline-block"></span> Menguji...';
                btn.disabled = true;

                try {
                    const response = await fetch('/admin/test-ai', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ endpoint: endpoint, api_key: apiKey })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Berhasil!', 'Koneksi ke Endpoint AI sukses terhubung.', 'success');
                        
                        // Populate models
                        const modelSelect = document.getElementById('ai_model');
                        modelSelect.innerHTML = '';
                        
                        if (data.models && data.models.length > 0) {
                            data.models.forEach(model => {
                                const option = document.createElement('option');
                                option.value = model;
                                option.text = model;
                                // Auto select gpt-4o or gpt-4-vision if available
                                if (model.includes('4o') || model.includes('vision') || model.includes('gemini-1.5')) {
                                    option.selected = true;
                                }
                                modelSelect.appendChild(option);
                            });
                            document.getElementById('model-hint').innerText = data.models.length + " model berhasil dimuat. Pilih model yang mendukung Vision (seperti gpt-4o).";
                            document.getElementById('model-hint').classList.add('text-green-600');
                        } else {
                            modelSelect.innerHTML = '<option value="{{ $ai_model }}">{{ $ai_model }} (Aktif / Default)</option>';
                            document.getElementById('model-hint').innerText = "Koneksi sukses, tetapi API tidak mengembalikan daftar model. Gunakan nilai default.";
                        }
                    } else {
                        Swal.fire('Gagal Terhubung', data.error || 'Terjadi kesalahan tidak dikenal.', 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Gagal memproses request jaringan.', 'error');
                }

                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Tes Koneksi';
                btn.disabled = false;
            }

            function toggleSettings() {
                const mode = document.getElementById('ocr_engine').value;
                document.getElementById('setting_n8n').classList.toggle('hidden', mode !== 'n8n');
                document.getElementById('setting_ai').classList.toggle('hidden', mode !== 'direct_ai');
            }
        </script>
        
    </div>
        </div> <!-- End Dashboard Content -->

        <!-- TAB CONTENT PERIODE -->
        <div id="content-periode" class="tab-content hidden animate-in fade-in duration-300">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Periode Pendaftaran</h2>
                    <p class="text-sm text-slate-500">Kelola tahun ajaran aktif untuk sistem pendaftaran.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Tambah -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Tambah Periode Baru
                        </h3>
                        <form action="{{ route('admin.periode.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5 text-xs uppercase tracking-wider">Nama Periode</label>
                                <input type="text" name="nama_periode" placeholder="Contoh: TA 2026/2027" class="w-full border border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-emerald-500 outline-none transition bg-slate-50" required>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                                <input type="checkbox" name="is_active" id="is_active_check" value="1" class="w-5 h-5 text-emerald-600 rounded">
                                <label for="is_active_check" class="text-sm font-bold text-slate-700 cursor-pointer">Jadikan Aktif Langsung</label>
                            </div>
                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-xl font-black shadow-lg shadow-emerald-500/20 transition transform active:scale-95">Simpan Periode</button>
                        </form>
                    </div>
                </div>

                <!-- Daftar Periode -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="p-4">Tahun Ajaran / Periode</th>
                                    <th class="p-4 text-center">Status Pendaftaran</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($periodes as $pr)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-4">
                                        <div class="font-extrabold text-slate-900">{{ $pr->nama_periode }}</div>
                                        <div class="text-[10px] text-slate-400 font-medium">Dibuat pada {{ $pr->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($pr->is_active)
                                            <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-black tracking-wider">AKTIF</span>
                                        @else
                                            <span class="bg-slate-100 text-slate-400 px-3 py-1 rounded-full text-[10px] font-black tracking-wider">NONAKTIF</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            @if(!$pr->is_active)
                                            <form action="{{ route('admin.periode.update', $pr->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="set_active" value="1">
                                                <button type="submit" class="text-xs font-bold text-emerald-600 hover:bg-emerald-50 px-3 py-1.5 rounded-lg transition border border-emerald-100">Aktifkan</button>
                                            </form>
                                            @endif
                                            <a href="{{ route('admin.periode.delete', $pr->id) }}" onclick="return confirm('Hapus periode ini?')" class="text-xs font-bold text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg transition border border-rose-100">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT LAPORAN -->
        <div id="content-laporan" class="tab-content hidden animate-in slide-in-from-bottom-4 duration-300">
            <div class="mb-8">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Laporan & Pusat Unduhan</h2>
                <p class="text-slate-500">Ekspor data pendaftar untuk keperluan administrasi offline atau pencetakan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Excel -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden group hover:border-emerald-300 transition duration-300">
                    <div class="p-8">
                        <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2 tracking-tight">Data Excel (CSV)</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-6">Unduh format spreadsheet lengkap yang berisi seluruh kolom data pendaftar untuk diolah di Microsoft Excel atau Google Sheets.</p>
                        <a href="{{ route('admin.export', ['format'=>'csv']) }}" class="inline-flex items-center justify-center w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-emerald-500/20 transition-all hover:-translate-y-1">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Unduh Sekarang (.csv)
                        </a>
                    </div>
                    <div class="bg-slate-50 px-8 py-4 border-t text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Direkomendasikan untuk Olah Data</div>
                </div>
                
                <!-- PDF -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden group hover:border-rose-300 transition duration-300">
                    <div class="p-8">
                        <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-600 mb-6 group-hover:scale-110 transition duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2 tracking-tight">Rekap Cetak (PDF)</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-6">Hasilkan dokumen PDF yang siap cetak untuk keperluan arsip fisik. Berisi ringkasan data pendaftar yang tersusun rapi.</p>
                        <a href="{{ route('admin.export', ['format'=>'pdf']) }}" target="_blank" class="inline-flex items-center justify-center w-full bg-rose-600 hover:bg-rose-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-rose-500/20 transition-all hover:-translate-y-1">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            Lihat & Cetak (.pdf)
                        </a>
                    </div>
                    <div class="bg-slate-50 px-8 py-4 border-t text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Direkomendasikan untuk Arsip Fisik</div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT LOG -->
        <div id="content-log" class="tab-content hidden">
            <h2 class="text-xl font-bold mb-4">Log Aktivitas Sistem</h2>
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 border-b">
                        <tr>
                            <th class="p-4">Waktu</th>
                            <th class="p-4">Aktivitas</th>
                            <th class="p-4">Aktor</th>
                            <th class="p-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($logs as $log)
                        <tr>
                            <td class="p-4 whitespace-nowrap text-xs text-slate-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="p-4 font-medium">{{ $log->aktivitas }}</td>
                            <td class="p-4"><span class="bg-slate-100 px-2 py-1 rounded text-xs border border-slate-200">{{ $log->aktor }}</span></td>
                            <td class="p-4 text-xs text-slate-400 font-mono">{{ $log->ip_address }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="p-8 text-center text-slate-500 italic">Belum ada aktivitas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
</body>
</html>
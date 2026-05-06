<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPSB Admin | Pusat Kendali Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #064e3b;
            --accent: #10b981;
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f8fafc;
            color: #1e293b;
        }
        .mesh-gradient {
            background-color: #064e3b;
            background-image: 
                radial-gradient(at 0% 0%, hsla(161,71%,42%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(164,81%,36%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(170,91%,28%,1) 0, transparent 50%);
            position: relative;
            overflow: hidden;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        .tab-btn {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .tab-btn.active {
            color: var(--primary);
            background: rgba(16, 185, 129, 0.05);
        }
        .tab-btn.active::after {
            content: "";
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: var(--primary);
            border-radius: 3px 3px 0 0;
            box-shadow: 0 -2px 10px rgba(16, 185, 129, 0.4);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .form-input {
            width: 100%; border: 1px solid #e2e8f0; border-radius: 0.75rem;
            padding: 0.75rem 1rem; font-size: 0.95rem; transition: all 0.2s;
            background-color: #f8fafc;
        }
        .form-input:focus {
            outline: none; border-color: var(--primary); background-color: #fff;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }
    </style>
</head>
<body class="bg-slate-50">
    <header class="mesh-gradient text-white pt-10 pb-20 px-8 relative overflow-hidden">
        <div class="max-w-6xl mx-auto relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-xl rounded-[1.5rem] flex items-center justify-center border border-white/30 shadow-2xl">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tighter uppercase">Admin<span class="text-emerald-300">SPSB</span></h1>
                    <p class="text-xs font-bold text-white/60 tracking-[0.3em] uppercase">Management Portal v2.5</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="text-right hidden md:block">
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Administrator</p>
                    <p class="text-lg font-black tracking-tight">{{ auth()->user()->username }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-white/10 hover:bg-rose-500 text-white px-8 py-3.5 rounded-2xl font-black flex items-center gap-3 transition-all active:scale-95 border border-white/10 hover:border-rose-400 group shadow-xl">
                        <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        LOGOUT
                    </button>
                </form>
            </div>
        </div>
    </header>

    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 px-6">
        <div class="max-w-6xl mx-auto flex overflow-x-auto no-scrollbar">
            <button id="tab-dashboard" onclick="switchTab('dashboard')" class="tab-btn active relative px-6 py-5 font-bold text-sm flex items-center gap-3 whitespace-nowrap transition-all group">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </button>
            <button id="tab-periode" onclick="switchTab('periode')" class="tab-btn relative px-6 py-5 font-bold text-sm flex items-center gap-3 whitespace-nowrap transition-all group text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Periode
            </button>
            <button id="tab-laporan" onclick="switchTab('laporan')" class="tab-btn relative px-6 py-5 font-bold text-sm flex items-center gap-3 whitespace-nowrap transition-all group text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Laporan
            </button>
            <button id="tab-log" onclick="switchTab('log')" class="tab-btn relative px-6 py-5 font-bold text-sm flex items-center gap-3 whitespace-nowrap transition-all group text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Log Aktivitas
            </button>
            <button id="tab-pengaturan" onclick="switchTab('pengaturan')" class="tab-btn relative px-6 py-5 font-bold text-sm flex items-center gap-3 whitespace-nowrap transition-all group text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Pengaturan
            </button>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6 lg:p-10 animate-fade">
        <div id="content-dashboard" class="tab-content block">
            
            <!-- Stats Cards Premium -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="glass-card rounded-[2rem] p-8 flex items-center justify-between shadow-xl shadow-blue-900/5 group hover:-translate-y-1 transition-all duration-300">
                    <div>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-[0.2em] mb-1">Total Pendaftar</p>
                        <p class="text-4xl font-black text-slate-800 tracking-tight">{{ $stats['total'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200 group-hover:rotate-6 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>
                
                <div class="glass-card rounded-[2rem] p-8 flex items-center justify-between shadow-xl shadow-amber-900/5 group hover:-translate-y-1 transition-all duration-300">
                    <div>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-[0.2em] mb-1">Menunggu Review</p>
                        <p class="text-4xl font-black text-slate-800 tracking-tight">{{ $stats['pending'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-amber-200 group-hover:rotate-6 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                <div class="glass-card rounded-[2rem] p-8 flex items-center justify-between shadow-xl shadow-emerald-900/5 group hover:-translate-y-1 transition-all duration-300">
                    <div>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-[0.2em] mb-1">Telah Diterima</p>
                        <p class="text-4xl font-black text-slate-800 tracking-tight">{{ $stats['diterima'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-emerald-200 group-hover:rotate-6 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Tabel Pendaftar Premium -->
            <div class="glass-card rounded-[2.5rem] shadow-2xl shadow-slate-900/5 border border-white overflow-hidden mb-8">
                <div class="p-8 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-6 bg-white/50">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Data Pendaftar Terbaru</h2>
                        <p class="text-xs text-slate-500 mt-1 font-bold italic opacity-70">Monitor pendaftaran calon peserta didik secara real-time.</p>
                    </div>
                    <form action="" method="GET" class="flex gap-3 w-full lg:w-auto">
                        <div class="relative w-full lg:w-72">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input type="text" name="search" placeholder="Cari nama atau nomor WA..." class="form-input pl-11 py-3 text-sm" value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-xl text-sm font-black hover:bg-emerald-600 transition shadow-xl shadow-slate-900/10 active:scale-95">CARI</button>
                    </form>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-400 uppercase tracking-[0.2em] font-black text-[10px]">
                            <tr>
                                <th class="p-6 w-12 text-center">No.</th>
                                <th class="p-6">Identitas Anak</th>
                                <th class="p-6 text-center">L/P</th>
                                <th class="p-6">Orang Tua</th>
                                <th class="p-6 text-center">Chat WA</th>
                                <th class="p-6 text-center">Status</th>
                                <th class="p-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($pendaftar as $p)
                            <tr class="hover:bg-emerald-50/30 transition-colors group">
                                <td class="p-6 text-center font-black text-slate-300 group-hover:text-emerald-500">{{ ($pendaftar->currentPage() - 1) * $pendaftar->perPage() + $loop->iteration }}.</td>
                                <td class="p-6">
                                    <div class="font-black text-slate-800 text-base">{{ $p->nama_lengkap }}</div>
                                    <div class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-0.5">{{ $p->nik ?? $p->nik_anak ?? 'Tanpa NIK' }}</div>
                                </td>
                                <td class="p-6 text-center">
                                    <span class="inline-flex w-8 h-8 items-center justify-center rounded-xl font-black text-xs {{ $p->jenis_kelamin == 'Laki-laki' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-pink-50 text-pink-600 border border-pink-100' }}">
                                        {{ $p->jenis_kelamin == 'Laki-laki' ? 'L' : 'P' }}
                                    </span>
                                </td>
                                <td class="p-6">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded uppercase">Ayah</span>
                                            <span class="text-slate-700 font-bold text-xs">{{ $p->nama_ayah }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded uppercase">Ibu</span>
                                            <span class="text-slate-700 font-bold text-xs">{{ $p->nama_ibu }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center justify-center gap-2">
                                        @if($p->no_wa_ayah)
                                        <a href="https://wa.me/{{ preg_replace('/^08/', '628', $p->no_wa_ayah) }}" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition shadow-sm border border-emerald-100 group/wa">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-6 text-center">
                                    @if($p->status_pendaftaran == 'Diterima')
                                        <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-4 py-2 rounded-2xl text-[10px] font-black tracking-widest border border-emerald-100 uppercase">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> DITERIMA
                                        </span>
                                    @elseif($p->status_pendaftaran == 'Ditolak')
                                        <span class="inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 px-4 py-2 rounded-2xl text-[10px] font-black tracking-widest border border-rose-100 uppercase">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> DITOLAK
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 px-4 py-2 rounded-2xl text-[10px] font-black tracking-widest border border-amber-100 uppercase">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-bounce"></span> REVIEW
                                        </span>
                                    @endif
                                </td>
                                <td class="p-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.show', $p->id) }}" class="bg-slate-900 text-white hover:bg-emerald-600 px-5 py-2.5 rounded-xl text-xs font-black transition-all shadow-lg shadow-slate-900/5 active:scale-95">Detail</a>
                                        <a href="{{ route('admin.edit', $p->id) }}" class="bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 px-5 py-2.5 rounded-xl text-xs font-black transition-all active:scale-95">Edit</a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-24 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-20 h-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
                                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        </div>
                                        <p class="text-slate-500 font-black text-lg tracking-tight">Belum ada data pendaftar.</p>
                                        <p class="text-xs text-slate-400 mt-1 font-bold italic opacity-60">Silakan periksa kembali filter pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                @if($pendaftar->hasPages())
                <div class="p-8 border-t border-slate-50 bg-slate-50/30 flex justify-center">
                    {{ $pendaftar->links('pagination::tailwind') }}
                </div>
                @endif
            </div>
        </div>

        <!-- TAB CONTENT PENGATURAN -->
        <div id="content-pengaturan" class="tab-content hidden animate-fade">
            <div class="mb-10">
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">Pengaturan Sistem Dasar</h2>
                <p class="text-slate-500 font-medium mt-1">Konfigurasi mesin AI, kop surat, dan keamanan dashboard.</p>
            </div>

            @if(session('success'))
                <div class="bg-emerald-50 text-emerald-700 p-5 mb-8 rounded-2xl font-bold border border-emerald-100 flex items-center gap-3 animate-fade shadow-sm">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                <div class="space-y-8">
                    <!-- Mesin OCR -->
                    <div class="glass-card rounded-[2rem] p-8 shadow-xl shadow-slate-900/5">
                        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                            @csrf
                            <div>
                                <label class="block font-black text-slate-700 text-sm uppercase tracking-widest mb-4">Mode Ekstraksi AI (OCR Engine)</label>
                                <select name="ocr_engine" id="ocr_engine" class="form-input font-bold text-slate-700 bg-white" onchange="toggleSettings()">
                                    <option value="local" {{ $ocr_engine == 'local' ? 'selected' : '' }}>Mode 1: Lokal Tesseract (Rendah)</option>
                                    <option value="n8n" {{ $ocr_engine == 'n8n' ? 'selected' : '' }}>Mode 2: Cloud n8n Webhook (Tinggi)</option>
                                    <option value="direct_ai" {{ $ocr_engine == 'direct_ai' ? 'selected' : '' }}>Mode 3: Direct API AI / LLM (Sangat Tinggi)</option>
                                </select>
                            </div>

                            <!-- Custom Kop Surat -->
                            <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 space-y-4">
                                <h3 class="font-black text-indigo-900 text-base flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg> 
                                    Kop Surat PDF
                                </h3>
                                <div class="space-y-3">
                                    <input type="text" name="kop_baris_1" class="form-input py-2.5 font-black text-sm" value="{{ $kop_baris_1 }}" placeholder="Nama Lembaga">
                                    <input type="text" name="kop_baris_2" class="form-input py-2.5 text-xs font-bold" value="{{ $kop_baris_2 }}" placeholder="Deskripsi/Sub-Judul">
                                    <input type="text" name="kop_baris_3" class="form-input py-2.5 text-[10px] font-medium" value="{{ $kop_baris_3 }}" placeholder="Alamat & Kontak">
                                </div>
                            </div>

                            <!-- Lock App -->
                            <div class="bg-rose-50/50 p-6 rounded-2xl border border-rose-100">
                                <label class="flex items-center gap-4 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" name="app_locked" value="1" {{ $app_locked ? 'checked' : '' }} class="w-12 h-6 bg-slate-300 rounded-full appearance-none checked:bg-rose-500 transition-colors cursor-pointer relative z-10">
                                        <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform transform translate-x-0 group-hover:scale-110 z-20" id="lock-switch"></div>
                                    </div>
                                    <span class="font-black text-rose-900 text-sm tracking-tight uppercase">Tutup Pendaftaran</span>
                                </label>
                                <p class="text-[10px] text-rose-500 font-bold mt-2 italic">* Jika aktif, pendaftar tidak dapat mengakses formulir.</p>
                            </div>

                            <!-- Tombol submit dipindah ke bawah agar mencakup semua field -->
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- n8n Config -->
                    <div id="setting_n8n" class="glass-card rounded-[2rem] p-8 shadow-xl shadow-blue-900/5 {{ $ocr_engine == 'n8n' ? 'block' : 'hidden' }}">
                        <h3 class="font-black text-blue-900 text-lg mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            Konfigurasi Webhook n8n
                        </h3>
                        <div>
                            <label class="block font-black text-slate-500 text-[10px] uppercase tracking-widest mb-2">Webhook URL</label>
                            <input type="text" name="ocr_webhook_url" id="ocr_webhook_url" class="form-input text-blue-600 font-mono text-xs" value="{{ $ocr_webhook_url }}" placeholder="https://n8n.domain.com/...">
                        </div>
                    </div>

                    <!-- Direct AI Config -->
                    <div id="setting_ai" class="glass-card rounded-[2rem] p-8 shadow-xl shadow-purple-900/5 {{ $ocr_engine == 'direct_ai' ? 'block' : 'hidden' }}">
                        <h3 class="font-black text-purple-900 text-lg mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            Direct AI API (LLM)
                        </h3>
                        <div class="space-y-5">
                            <div>
                                <label class="block font-black text-slate-500 text-[10px] uppercase tracking-widest mb-2">Endpoint API URL</label>
                                <input type="text" id="ai_endpoint" name="ai_endpoint" class="form-input text-purple-600 font-mono text-xs" value="{{ $ai_endpoint }}">
                            </div>
                            <div>
                                <label class="block font-black text-slate-500 text-[10px] uppercase tracking-widest mb-2">API Key</label>
                                <div class="flex gap-2">
                                    <input type="password" id="ai_api_key" name="ai_api_key" class="form-input text-purple-600 font-mono text-xs" value="{{ $ai_api_key }}">
                                    <button type="button" id="btn-test-ai" onclick="testConnection()" class="bg-purple-600 hover:bg-purple-700 text-white font-black px-4 rounded-xl shadow-lg transition active:scale-95 whitespace-nowrap text-xs">TES KONEKSI</button>
                                </div>
                            </div>
                            <div>
                                <label class="block font-black text-slate-500 text-[10px] uppercase tracking-widest mb-2">Pilih Model AI</label>
                                <select id="ai_model_select" class="form-input font-bold text-slate-700 bg-white mb-3" onchange="handleModelChange()">
                                    <option value="{{ $ai_model }}">{{ $ai_model }}</option>
                                    <option value="gpt-4o">gpt-4o</option>
                                    <option value="gpt-4o-mini">gpt-4o-mini</option>
                                    <option value="gemini-1.5-pro">gemini-1.5-pro</option>
                                    <option value="custom">-- Ketik Nama Model Manual --</option>
                                </select>
                                
                                <div id="custom_model_container" class="hidden animate-fade">
                                    <input type="text" id="ai_model_custom" class="form-input font-bold text-emerald-600 bg-emerald-50 border-emerald-200" placeholder="Masukkan ID model (misal: doubao-pro-4k)">
                                </div>

                                <input type="hidden" id="ai_model" name="ai_model" value="{{ $ai_model }}">
                                <p class="text-[10px] text-slate-400 font-bold mt-2 italic" id="model-hint">Pilih dari daftar atau gunakan 'Ketik Manual' untuk model provider kustom.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Simpan Utama (mencakup semua field termasuk AI) -->
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-slate-900 hover:bg-emerald-600 text-white py-4 rounded-2xl font-black transition-all shadow-xl shadow-slate-900/10 active:scale-95 text-sm tracking-widest uppercase">💾 Simpan Semua Pengaturan</button>
                    </div>

                    </form>{{-- Tutup form di sini agar mencakup semua field --}}

                    <!-- Account Security -->
                    <div class="glass-card rounded-[2rem] p-8 shadow-xl shadow-rose-900/5">
                        <h3 class="font-black text-rose-900 text-lg mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center text-rose-600 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            Keamanan Akun Admin
                        </h3>
                        <form action="{{ route('admin.account.update') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block font-black text-slate-500 text-[10px] uppercase tracking-widest mb-2">Username Baru</label>
                                <input type="text" name="username" class="form-input font-black text-slate-800" value="{{ auth()->user()->username }}" required>
                            </div>
                            <div>
                                <label class="block font-black text-slate-500 text-[10px] uppercase tracking-widest mb-2">Password Baru (Opsional)</label>
                                <input type="password" name="password" class="form-input" placeholder="••••••••">
                            </div>
                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white py-3 rounded-2xl font-black transition-all shadow-xl shadow-rose-500/10 active:scale-95">UPDATE KREDENSIAL</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT PERIODE -->
        <div id="content-periode" class="tab-content hidden animate-fade">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight">Periode Pendaftaran</h2>
                    <p class="text-slate-500 font-medium">Kelola tahun ajaran aktif sistem.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 items-start">
                <!-- Form Tambah -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-[2rem] p-8 shadow-xl shadow-slate-900/5">
                        <h3 class="font-black text-slate-800 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </div>
                            Tambah Periode
                        </h3>
                        <form action="{{ route('admin.periode.store') }}" method="POST" class="space-y-6">
                            @csrf
                            <div>
                                <label class="block font-black text-slate-500 text-[10px] uppercase tracking-widest mb-2">Nama Periode</label>
                                <input type="text" name="nama_periode" placeholder="Contoh: TA 2026/2027" class="form-input font-black text-slate-800" required>
                            </div>
                            <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer group transition-colors hover:border-emerald-200">
                                <input type="checkbox" name="is_active" value="1" class="w-5 h-5 text-emerald-600 rounded-lg cursor-pointer">
                                <span class="text-xs font-black text-slate-600 group-hover:text-emerald-700">Jadikan Periode Aktif</span>
                            </label>
                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-4 rounded-2xl font-black shadow-xl shadow-emerald-500/20 transition-all active:scale-95">SIMPAN PERIODE</button>
                        </form>
                    </div>
                </div>

                <!-- Daftar Periode -->
                <div class="lg:col-span-3">
                    <div class="glass-card rounded-[2.5rem] shadow-2xl shadow-slate-900/5 overflow-hidden">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-400 uppercase tracking-[0.2em] font-black text-[10px]">
                                <tr>
                                    <th class="p-6">Periode / Tahun Ajaran</th>
                                    <th class="p-6 text-center">Status</th>
                                    <th class="p-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($periodes as $pr)
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 text-base group-hover:text-emerald-600 transition-colors">{{ $pr->nama_periode }}</div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Dibuat {{ $pr->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="p-6 text-center">
                                        @if($pr->is_active)
                                            <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-4 py-2 rounded-2xl text-[10px] font-black tracking-widest border border-emerald-100 uppercase animate-pulse">
                                                AKTIF
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-400 px-4 py-2 rounded-2xl text-[10px] font-black tracking-widest border border-slate-200 uppercase opacity-60">
                                                NONAKTIF
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex justify-end gap-3">
                                            @if(!$pr->is_active)
                                            <form action="{{ route('admin.periode.update', $pr->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="set_active" value="1">
                                                <button type="submit" class="bg-white text-emerald-600 border border-emerald-100 hover:bg-emerald-600 hover:text-white px-4 py-2 rounded-xl text-[10px] font-black transition-all uppercase tracking-widest active:scale-95">Aktifkan</button>
                                            </form>
                                            @endif
                                            <a href="{{ route('admin.periode.delete', $pr->id) }}" onclick="return confirm('Hapus periode ini?')" class="bg-white text-rose-500 border border-rose-100 hover:bg-rose-600 hover:text-white px-4 py-2 rounded-xl text-[10px] font-black transition-all uppercase tracking-widest active:scale-95">Hapus</a>
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
        <div id="content-laporan" class="tab-content hidden animate-fade">
            <div class="mb-10">
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">Pusat Laporan & Unduhan</h2>
                <p class="text-slate-500 font-medium">Ekspor database pendaftar ke format Excel atau PDF.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="glass-card rounded-[3rem] p-10 shadow-2xl shadow-emerald-900/5 group hover:-translate-y-2 transition-all duration-500 border border-white relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-emerald-50 rounded-full blur-3xl opacity-60 group-hover:bg-emerald-200 transition-colors duration-500"></div>
                    <div class="w-20 h-20 bg-emerald-100 rounded-[2rem] flex items-center justify-center text-emerald-600 mb-8 shadow-inner group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Data Spreadsheet (.CSV)</h3>
                    <p class="text-slate-500 font-medium leading-relaxed mb-8">Berisi seluruh kolom data pendaftar mentah yang siap diolah menggunakan Microsoft Excel atau Google Sheets.</p>
                    <a href="{{ route('admin.export', ['format'=>'csv']) }}" class="inline-flex items-center justify-center w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-5 rounded-[1.5rem] shadow-xl shadow-emerald-500/20 transition-all hover:shadow-emerald-500/40 active:scale-95">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        UNDUH FORMAT EXCEL
                    </a>
                </div>

                <div class="glass-card rounded-[3rem] p-10 shadow-2xl shadow-rose-900/5 group hover:-translate-y-2 transition-all duration-500 border border-white relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-rose-50 rounded-full blur-3xl opacity-60 group-hover:bg-rose-200 transition-colors duration-500"></div>
                    <div class="w-20 h-20 bg-rose-100 rounded-[2rem] flex items-center justify-center text-rose-600 mb-8 shadow-inner group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Rekapitulasi Cetak (.PDF)</h3>
                    <p class="text-slate-500 font-medium leading-relaxed mb-8">Hasilkan dokumen PDF rapi yang siap cetak untuk keperluan arsip fisik pendaftaran lembaga.</p>
                    <a href="{{ route('admin.export', ['format'=>'pdf']) }}" target="_blank" class="inline-flex items-center justify-center w-full bg-rose-600 hover:bg-rose-700 text-white font-black py-5 rounded-[1.5rem] shadow-xl shadow-rose-500/20 transition-all hover:shadow-rose-500/40 active:scale-95">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        LIHAT & CETAK PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT LOG -->
        <div id="content-log" class="tab-content hidden animate-fade">
            <div class="mb-10">
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">Log Aktivitas Sistem</h2>
                <p class="text-slate-500 font-medium">Rekaman jejak aktivitas admin secara transparan.</p>
            </div>
            <div class="glass-card rounded-[2.5rem] shadow-2xl shadow-slate-900/5 overflow-hidden">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-400 uppercase tracking-[0.2em] font-black text-[10px]">
                        <tr>
                            <th class="p-6">Waktu</th>
                            <th class="p-6">Aktivitas & Keterangan</th>
                            <th class="p-6">Aktor</th>
                            <th class="p-6">Alamat IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="p-6 text-xs text-slate-400 font-bold tracking-tight uppercase">{{ $log->created_at->format('d/m/Y') }} <span class="text-slate-300 ml-1 font-medium">{{ $log->created_at->format('H:i:s') }}</span></td>
                            <td class="p-6 font-black text-slate-700 text-sm tracking-tight">{{ $log->aktivitas }}</td>
                            <td class="p-6"><span class="bg-slate-100 text-slate-500 px-3 py-1.5 rounded-xl text-[10px] font-black border border-slate-200 uppercase tracking-widest">{{ $log->aktor }}</span></td>
                            <td class="p-6 text-[10px] text-slate-400 font-mono group-hover:text-emerald-500 transition-colors">{{ $log->ip_address }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="p-24 text-center text-slate-400 font-bold italic">Belum ada rekaman aktivitas sistem.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <!-- FLOATING AI CHATBOT -->
    @if($ai_api_key)
    <div class="fixed bottom-8 right-8 z-[100]">
        <!-- Chat Button -->
        <button id="chat-toggle" class="w-16 h-16 bg-slate-900 text-white rounded-full flex items-center justify-center shadow-2xl hover:scale-110 active:scale-95 transition-all duration-300 group">
            <svg class="w-8 h-8 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white animate-pulse"></span>
        </button>

        <!-- Chat Window -->
        <div id="chat-window" class="hidden absolute bottom-20 right-0 w-[400px] max-h-[600px] glass-card rounded-[2.5rem] shadow-2xl border border-white/50 flex flex-col overflow-hidden animate-fade">
            <!-- Header -->
            <div class="mesh-gradient p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center border border-white/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black tracking-tight">Asisten AI SPSB</p>
                            <p class="text-[10px] font-bold text-emerald-300 uppercase tracking-widest">Online • Data Ready</p>
                        </div>
                    </div>
                    <button id="close-chat" class="text-white/60 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
            </div>

            <!-- Messages Container -->
            <div id="chat-messages" class="flex-1 p-6 overflow-y-auto space-y-4 min-h-[300px] max-h-[400px] bg-white/30 no-scrollbar">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs flex-shrink-0">AI</div>
                    <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm text-sm text-slate-700 leading-relaxed border border-slate-100">
                        Halo Admin! Saya sudah membaca data pendaftar. Ada yang ingin ditanyakan seputar data hari ini?
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-6 bg-white/50 border-t border-slate-100">
                <form id="chat-form" class="flex gap-2">
                    <input type="text" id="chat-input" class="form-input text-sm py-3 px-4" placeholder="Tanya tentang pendaftar..." required>
                    <button type="submit" id="chat-submit" class="bg-slate-900 text-white w-12 h-12 rounded-xl flex items-center justify-center hover:bg-emerald-600 transition shadow-lg flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script>
        // Chatbot Logic
        @if($ai_api_key)
        const chatToggle = document.getElementById('chat-toggle');
        const chatWindow = document.getElementById('chat-window');
        const closeChat = document.getElementById('close-chat');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const chatMessages = document.getElementById('chat-messages');

        chatToggle.addEventListener('click', () => chatWindow.classList.toggle('hidden'));
        closeChat.addEventListener('click', () => chatWindow.classList.add('hidden'));

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const question = chatInput.value.trim();
            if(!question) return;

            // Add User Message
            addMessage('user', question);
            chatInput.value = '';

            // Add Loading Message
            const loadingId = 'msg-' + Date.now();
            addMessage('ai', '<span class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-slate-300 rounded-full animate-bounce"></span><span class="w-1.5 h-1.5 bg-slate-300 rounded-full animate-bounce delay-75"></span><span class="w-1.5 h-1.5 bg-slate-300 rounded-full animate-bounce delay-150"></span> Berpikir...</span>', loadingId);

            try {
                const response = await fetch('{{ route("admin.ask_ai") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ question: question })
                });

                const data = await response.json();
                const loadingEl = document.getElementById(loadingId);
                
                if(data.success) {
                    loadingEl.innerHTML = data.answer.replace(/\n/g, '<br>');
                } else {
                    loadingEl.innerHTML = '<span class="text-rose-500 font-bold">' + (data.error || 'Terjadi kesalahan sistem.') + '</span>';
                }
            } catch (error) {
                const loadingEl = document.getElementById(loadingId);
                loadingEl.innerHTML = '<span class="text-rose-500 font-bold">Koneksi Gagal. Silakan coba lagi.</span>';
            }
            
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });

        function addMessage(role, text, id = null) {
            const div = document.createElement('div');
            div.className = 'flex items-start gap-3 ' + (role === 'user' ? 'flex-row-reverse' : '');
            
            const icon = role === 'user' ? 
                '<div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 shadow-sm">ME</div>' : 
                '<div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs flex-shrink-0">AI</div>';

            const bubbleClass = role === 'user' ? 
                'bg-emerald-600 text-white rounded-tr-none' : 
                'bg-white text-slate-700 rounded-tl-none border border-slate-100';

            div.innerHTML = `
                ${icon}
                <div id="${id || ''}" class="${bubbleClass} p-4 rounded-2xl shadow-sm text-sm leading-relaxed">
                    ${text}
                </div>
            `;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        @endif

        function switchTab(tabId) {
            // Hide all
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            // Reset buttons
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active', 'text-emerald-600');
                el.classList.add('text-slate-500');
            });
            
            // Show & Active
            const content = document.getElementById('content-' + tabId);
            if(content) content.classList.remove('hidden');
            
            const btn = document.getElementById('tab-' + tabId);
            if(btn) {
                btn.classList.add('active', 'text-emerald-600');
                btn.classList.remove('text-slate-500');
            }
        }
// ... rest of script ...

        async function testConnection() {
            const endpoint = document.getElementById('ai_endpoint').value;
            const apiKey = document.getElementById('ai_api_key').value;
            const btn = document.getElementById('btn-test-ai');

            if (!endpoint || !apiKey) {
                Swal.fire({
                    title: 'Data Tidak Lengkap',
                    text: 'Harap isi Endpoint URL dan API Key terlebih dahulu!',
                    icon: 'warning',
                    confirmButtonColor: '#059669',
                    customClass: { popup: 'rounded-[2rem]' }
                });
                return;
            }

            btn.innerHTML = '<span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block mr-2"></span> MENGUJI...';
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
                    Swal.fire({
                        title: 'Koneksi Sukses!',
                        text: 'SPSB berhasil terhubung ke Endpoint AI.',
                        icon: 'success',
                        confirmButtonColor: '#059669',
                        customClass: { popup: 'rounded-[2rem]' }
                    });
                    
                    const modelSelect = document.getElementById('ai_model_select');
                    const currentVal = modelSelect.value;
                    modelSelect.innerHTML = '';
                    
                    if (data.models && data.models.length > 0) {
                        data.models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            option.text = model;
                            modelSelect.appendChild(option);
                        });
                    }
                    
                    // Always add custom option
                    const customOpt = document.createElement('option');
                    customOpt.value = 'custom';
                    customOpt.text = '-- Ketik Nama Model Manual --';
                    modelSelect.appendChild(customOpt);

                    document.getElementById('model-hint').innerText = (data.models ? data.models.length : 0) + " model disarankan. " + (data.note || "");
                    document.getElementById('model-hint').classList.replace('text-slate-400', 'text-emerald-600');
                    
                    handleModelChange();
                } else {
                    Swal.fire({
                        title: 'Koneksi Gagal',
                        text: data.error || 'Periksa API Key Anda.',
                        icon: 'error',
                        confirmButtonColor: '#e11d48',
                        customClass: { popup: 'rounded-[2rem]' }
                    });
                }
            } catch (error) {
                Swal.fire('Error', 'Gagal memproses request jaringan.', 'error');
            }

            btn.innerHTML = 'TES KONEKSI';
            btn.disabled = false;
        }

        function toggleSettings() {
            const mode = document.getElementById('ocr_engine').value;
            document.getElementById('setting_n8n').classList.toggle('hidden', mode !== 'n8n');
            document.getElementById('setting_ai').classList.toggle('hidden', mode !== 'direct_ai');
        }

        function handleModelChange() {
            const select = document.getElementById('ai_model_select');
            const customContainer = document.getElementById('custom_model_container');
            const customInput = document.getElementById('ai_model_custom');
            const hiddenInput = document.getElementById('ai_model');

            if (select.value === 'custom') {
                customContainer.classList.remove('hidden');
                hiddenInput.value = customInput.value;
                
                customInput.oninput = () => {
                    hiddenInput.value = customInput.value;
                };
            } else {
                customContainer.classList.add('hidden');
                hiddenInput.value = select.value;
            }
        }

        // Initialize on load
        window.addEventListener('load', () => {
            handleModelChange();
            
            const hash = window.location.hash.replace('#', '');
            if(['dashboard', 'periode', 'laporan', 'log', 'pengaturan'].includes(hash)) {
                switchTab(hash);
            }
        });
    </script>
</body>
</html>

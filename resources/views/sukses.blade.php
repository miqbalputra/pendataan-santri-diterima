<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendataan Berhasil | SPSB Griya Qur'an</title>
    <meta name="description" content="Pendataan Peserta Didik Baru berhasil dikirim ke sistem SPSB Griya Qur'an">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); min-height: 100vh; overflow-x: hidden; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-12px) rotate(2deg); }
            66% { transform: translateY(-6px) rotate(-1deg); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        @keyframes checkmark-draw {
            0% { stroke-dashoffset: 100; }
            100% { stroke-dashoffset: 0; }
        }
        @keyframes orbit {
            0% { transform: rotate(0deg) translateX(120px) rotate(0deg); }
            100% { transform: rotate(360deg) translateX(120px) rotate(-360deg); }
        }
        
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-fade-up { animation: fadeInUp 0.8s ease-out forwards; }
        .animate-fade-scale { animation: fadeInScale 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .animate-shimmer {
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.08) 50%, transparent 100%);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        .confetti-piece {
            position: fixed;
            width: 10px;
            height: 10px;
            top: -20px;
            animation: confetti-fall linear forwards;
            z-index: 100;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .success-glow {
            box-shadow: 0 0 60px rgba(16, 185, 129, 0.15), 0 0 120px rgba(16, 185, 129, 0.05);
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <!-- Confetti Particles -->
    <div id="confetti-container"></div>

    <!-- Floating Orbs Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-teal-500/5 rounded-full blur-3xl animate-float" style="animation-delay: -2s;"></div>
        <div class="absolute top-1/2 left-1/2 w-48 h-48 bg-cyan-500/5 rounded-full blur-3xl animate-float" style="animation-delay: -4s;"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 w-full max-w-lg mx-auto">
        <!-- Success Card -->
        <div class="glass-card rounded-[2.5rem] p-8 md:p-12 success-glow animate-fade-up" style="animation-delay: 0.2s;">
            
            <!-- Animated Checkmark -->
            <div class="flex justify-center mb-8">
                <div class="relative">
                    <!-- Pulse rings -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-28 h-28 rounded-full border-2 border-emerald-500/20" style="animation: pulse-ring 2s ease-out infinite;"></div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-28 h-28 rounded-full border-2 border-emerald-500/10" style="animation: pulse-ring 2s ease-out infinite 0.5s;"></div>
                    </div>
                    
                    <!-- Check Circle -->
                    <div class="w-28 h-28 bg-gradient-to-br from-emerald-500 to-teal-400 rounded-full flex items-center justify-center animate-fade-scale shadow-2xl shadow-emerald-500/30" style="animation-delay: 0.5s;">
                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke-dasharray: 100; animation: checkmark-draw 0.8s ease-out 0.8s forwards; stroke-dashoffset: 100;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center animate-fade-up" style="animation-delay: 0.6s;">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-emerald-400/70 mb-3">Pendataan Berhasil</p>
                <h1 class="text-2xl md:text-3xl font-black text-white leading-tight mb-3">
                    Alhamdulillah! 🎉
                </h1>
                <p class="text-slate-400 text-sm md:text-base font-medium leading-relaxed">
                    Data peserta didik baru telah <span class="text-emerald-400 font-bold">berhasil dikirim</span> dan tersimpan di sistem kami.
                </p>
            </div>

            <!-- Divider -->
            <div class="my-8 h-px bg-gradient-to-r from-transparent via-slate-600 to-transparent"></div>

            <!-- Info Cards -->
            <div class="space-y-3 animate-fade-up" style="animation-delay: 0.8s;">
                @if(session('nama_santri'))
                <div class="flex items-center gap-4 bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                    <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nama Peserta Didik</p>
                        <p class="text-white font-bold text-sm">{{ session('nama_santri') }}</p>
                    </div>
                </div>
                @endif

                @if(session('nomor_pendaftaran'))
                <div class="flex items-center gap-4 bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                    <div class="w-10 h-10 bg-cyan-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6M5 3h14a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Pendataan</p>
                        <p class="text-white font-bold text-sm">{{ session('nomor_pendaftaran') }}</p>
                    </div>
                </div>
                @endif

                <div class="flex items-center gap-4 bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                    <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Status Verifikasi Data</p>
                        <p class="text-amber-400 font-bold text-sm">Menunggu Verifikasi Data</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 bg-white/[0.03] rounded-2xl p-4 border border-white/5">
                    <div class="w-10 h-10 bg-cyan-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Informasi Selanjutnya</p>
                        <p class="text-slate-300 font-medium text-xs leading-relaxed">Panitia akan menghubungi Anda melalui nomor WhatsApp yang terdaftar untuk informasi verifikasi data.</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 space-y-3 animate-fade-up" style="animation-delay: 1s;">
                <a href="/" class="block w-full text-center bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white py-4 rounded-2xl font-bold text-sm uppercase tracking-wider transition-all duration-300 shadow-xl shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5">
                    Isi Data Peserta Didik Lainnya
                </a>
                <a href="{{ route('pendaftaran.cek_status') }}" class="block w-full text-center bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white py-3.5 rounded-2xl font-bold text-xs uppercase tracking-widest transition-all duration-300 border border-white/5 hover:border-white/10">
                    Cek Status Pendataan
                </a>
                @if(session('bukti_url'))
                <a href="{{ session('bukti_url') }}" target="_blank" class="block w-full text-center bg-white text-emerald-700 hover:bg-emerald-50 py-3.5 rounded-2xl font-bold text-xs uppercase tracking-widest transition-all duration-300">
                    Cetak Bukti Pendataan
                </a>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-slate-600 text-[10px] font-bold tracking-widest uppercase mt-8 animate-fade-up" style="animation-delay: 1.2s;">
            &copy; {{ date('Y') }} • Griya Qur'an x Tunas Ilmu
        </p>
    </div>

    <!-- Confetti Script -->
    <script>
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            const colors = ['#10b981', '#14b8a6', '#06b6d4', '#f59e0b', '#ec4899', '#8b5cf6', '#ef4444', '#3b82f6'];
            const shapes = ['rounded-full', 'rounded-sm', ''];
            
            for (let i = 0; i < 60; i++) {
                const piece = document.createElement('div');
                piece.className = `confetti-piece ${shapes[Math.floor(Math.random() * shapes.length)]}`;
                piece.style.left = Math.random() * 100 + 'vw';
                piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                piece.style.width = (Math.random() * 8 + 4) + 'px';
                piece.style.height = (Math.random() * 8 + 4) + 'px';
                piece.style.animationDuration = (Math.random() * 3 + 2) + 's';
                piece.style.animationDelay = (Math.random() * 2) + 's';
                piece.style.opacity = Math.random() * 0.8 + 0.2;
                container.appendChild(piece);
            }
            
            // Clean up after animation
            setTimeout(() => {
                container.innerHTML = '';
            }, 6000);
        }
        
        // Launch confetti on page load
        window.addEventListener('DOMContentLoaded', createConfetti);
    </script>
</body>
</html>

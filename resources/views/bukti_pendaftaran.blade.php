<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pendaftaran - {{ $santri->nama_lengkap }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .sheet { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="no-print mb-5 flex justify-between gap-3">
            <a href="{{ url('/') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600">Kembali</a>
            <button onclick="window.print()" class="rounded-xl bg-emerald-700 px-5 py-2 text-sm font-extrabold text-white">Cetak / Simpan PDF</button>
        </div>

        <section class="sheet bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-br from-emerald-800 to-teal-700 p-8 text-white">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.3em] text-emerald-100">Bukti Pendaftaran SPSB</p>
                        <h1 class="mt-3 text-3xl font-black tracking-tight">Pendaftaran Berhasil Diterima Sistem</h1>
                        <p class="mt-2 text-emerald-50/85 font-semibold">Griya Qur'an & PKBM Tunas Ilmu</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 border border-white/20 p-4 text-right">
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-100">Nomor Pendaftaran</p>
                        <p class="mt-1 text-2xl font-black">{{ $santri->nomor_pendaftaran }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8 grid md:grid-cols-[1fr_220px] gap-8">
                <div class="space-y-5">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Nama Peserta Didik</p>
                            <p class="mt-1 font-extrabold text-lg">{{ $santri->nama_lengkap }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">NIK</p>
                            <p class="mt-1 font-extrabold text-lg">{{ $santri->nik }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Jenis Kelamin</p>
                            <p class="mt-1 font-extrabold">{{ $santri->jenis_kelamin }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Status</p>
                            <p class="mt-1 font-extrabold">{{ $santri->status_pendaftaran }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Periode</p>
                            <p class="mt-1 font-extrabold">{{ optional($santri->periode)->nama_periode ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Gelombang</p>
                            <p class="mt-1 font-extrabold">{{ optional($santri->gelombang)->nama_gelombang ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <p class="font-extrabold text-amber-900">Simpan bukti ini.</p>
                        <p class="mt-1 text-sm font-semibold text-amber-800">Panitia dapat memindai QR untuk validasi data dan membuka detail pendaftar di dashboard admin.</p>
                    </div>
                </div>

                <aside class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">QR Validasi Panitia</p>
                    <img src="{{ $qrUrl }}" class="mx-auto h-44 w-44" alt="QR Validasi">
                    <p class="mt-3 break-all text-[10px] font-semibold text-slate-400">{{ $adminUrl }}</p>
                </aside>
            </div>
        </section>
    </main>
</body>
</html>

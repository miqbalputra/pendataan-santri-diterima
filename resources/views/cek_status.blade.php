<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran | SPSB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900">
    <main class="max-w-3xl mx-auto px-4 py-10">
        <a href="{{ url('/') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">Kembali ke Form</a>

        <section class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-sm p-6 md:p-8">
            <p class="text-xs font-extrabold text-emerald-700 uppercase tracking-widest">SPSB Online</p>
            <h1 class="text-3xl font-extrabold tracking-tight mt-2">Cek Status Pendaftaran</h1>
            <p class="text-slate-500 mt-2">Masukkan nomor pendaftaran resmi atau NIK anak untuk melihat status data.</p>

            @if(session('success'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('pendaftaran.cari_status') }}" class="mt-6 flex flex-col md:flex-row gap-3">
                @csrf
                <input name="kata_kunci" value="{{ old('kata_kunci', $keyword ?? '') }}" class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold focus:border-emerald-500 focus:outline-none" placeholder="Contoh: SPSB-2026-00001 atau NIK" required>
                <button class="rounded-xl bg-emerald-700 px-6 py-3 font-extrabold text-white hover:bg-emerald-800">CEK STATUS</button>
            </form>
            @error('kata_kunci')
                <p class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</p>
            @enderror
        </section>

        @if(isset($keyword))
            @if($santri)
                @php
                    $dokumenStatus = collect($santri->dokumen_status ?? []);
                    $perluPerbaikan = $dokumenStatus->contains('perlu_perbaikan');
                    $sudahDiterima = $santri->status_pendaftaran === 'Diterima';
                    $ditolak = $santri->status_pendaftaran === 'Ditolak';
                    $dokumenLabel = $sudahDiterima ? 'Selesai Diverifikasi' : ($ditolak ? 'Tidak Dilanjutkan' : ($perluPerbaikan ? 'Perlu Perbaikan' : 'Menunggu Review'));
                    $labels = [
                        'foto_akta_anak' => 'Akta Kelahiran',
                        'foto_kk' => 'Kartu Keluarga',
                        'foto_ktp_ayah' => 'KTP Bapak',
                        'foto_ktp_ibu' => 'KTP Ibu',
                        'foto_pas_siswa' => 'Foto Anak',
                    ];
                @endphp
                <section class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-6 md:p-8 border-b border-slate-100">
                        <p class="text-xs font-extrabold text-slate-400 uppercase tracking-widest">Nomor Pendaftaran</p>
                        <h2 class="text-2xl font-extrabold mt-1">{{ $santri->nomor_pendaftaran }}</h2>
                        <p class="text-slate-500 mt-1">{{ $santri->nama_lengkap }} | {{ $santri->jenis_kelamin }}</p>
                    </div>
                    <div class="grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                        <div class="p-6">
                            <p class="text-xs font-bold uppercase text-slate-400">Status Pendaftaran</p>
                            <p class="mt-2 text-lg font-extrabold {{ $santri->status_pendaftaran === 'Diterima' ? 'text-emerald-700' : ($santri->status_pendaftaran === 'Ditolak' ? 'text-rose-700' : 'text-amber-700') }}">{{ $santri->status_pendaftaran }}</p>
                        </div>
                        <div class="p-6">
                            <p class="text-xs font-bold uppercase text-slate-400">Status Dokumen</p>
                            <p class="mt-2 text-lg font-extrabold {{ $perluPerbaikan ? 'text-rose-700' : ($ditolak ? 'text-slate-600' : 'text-emerald-700') }}">{{ $dokumenLabel }}</p>
                        </div>
                        <div class="p-6">
                            <p class="text-xs font-bold uppercase text-slate-400">Tanggal Daftar</p>
                            <p class="mt-2 text-lg font-extrabold">{{ $santri->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8">
                        <h3 class="font-extrabold">Ringkasan Verifikasi Dokumen</h3>
                        <div class="mt-4 space-y-2">
                            @foreach($labels as $field => $label)
                                @php $status = $santri->dokumen_status[$field] ?? 'menunggu_review'; @endphp
                                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                                    <span class="text-sm font-bold text-slate-700">{{ $label }}</span>
                                    <span class="text-[11px] font-extrabold uppercase {{ $status === 'valid' ? 'text-emerald-700' : ($status === 'perlu_perbaikan' ? 'text-rose-700' : 'text-amber-700') }}">{{ str_replace('_', ' ', $status) }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if($santri->dokumen_catatan)
                            <div class="mt-5 rounded-xl bg-amber-50 border border-amber-200 p-4">
                                <p class="text-xs font-extrabold uppercase text-amber-700">Catatan Panitia</p>
                                <p class="mt-1 text-sm font-semibold text-amber-900">{{ $santri->dokumen_catatan }}</p>
                            </div>
                        @endif

                        @if($perluPerbaikan)
                            <a href="{{ route('pendaftaran.revisi', $santri->revisi_token) }}" class="mt-5 inline-flex rounded-xl bg-slate-900 px-5 py-3 text-sm font-extrabold text-white hover:bg-emerald-700">REVISI DATA / DOKUMEN</a>
                        @endif
                    </div>
                </section>
            @else
                <section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-800 font-bold">
                    Data tidak ditemukan. Pastikan nomor pendaftaran atau NIK sudah benar.
                </section>
            @endif
        @endif
    </main>
</body>
</html>

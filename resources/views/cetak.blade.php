<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pendataan - {{ $santri->nama_lengkap }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-border { border: 1px solid #000 !important; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 pb-12 pt-6">
    @php
        $statusLabel = $santri->status_pendaftaran === 'Diterima'
            ? 'Data Lengkap / Terverifikasi'
            : ($santri->status_pendaftaran === 'Ditolak' ? 'Data Tidak Valid / Tidak Dilanjutkan' : 'Menunggu Verifikasi Data');
    @endphp
    <div class="max-w-3xl mx-auto bg-white p-8 sm:p-12 shadow-xl print-border relative">
        <!-- Print Button -->
        <button onclick="window.print()" class="no-print absolute top-6 right-6 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print / Save PDF
        </button>

        <!-- Header -->
        <div class="text-center border-b-4 border-emerald-800 pb-6 mb-8">
            <h1 class="text-3xl font-black uppercase text-emerald-900 tracking-wider">BUKTI PENDATAAN</h1>
            <h2 class="text-xl font-bold mt-2">Kelompok Tahfidz Griya Qur'an & PKBM Tunas Ilmu</h2>
            <p class="text-sm text-slate-600 mt-1">Program Paket A (Setara SD) Tahun Ajaran 2025 - 2026</p>
            <p class="text-xs text-slate-500 mt-2">Waktu Data Masuk: {{ $santri->created_at->format('d/m/Y H:i:s') }}</p>
        </div>

        <div class="mb-4 bg-emerald-50 text-emerald-800 px-4 py-3 border border-emerald-200 rounded font-bold text-center">
            STATUS: <span class="uppercase">{{ $statusLabel }}</span>
        </div>

        <!-- Data -->
        <h3 class="font-bold text-lg border-b pb-1 mb-4 text-emerald-800">A. Data Peserta Didik</h3>
        <table class="w-full text-sm mb-8">
            <tr><td class="py-2 w-1/3 text-slate-600 font-medium">Nama Lengkap</td><td class="font-bold">: {{ $santri->nama_lengkap }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">NIK / NISN</td><td>: {{ $santri->nik }} / {{ $santri->nisn ?? '-' }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">Tempat, Tanggal Lahir</td><td>: {{ $santri->tempat_lahir }}, {{ \Carbon\Carbon::parse($santri->tanggal_lahir)->format('d F Y') }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">Jenis Kelamin</td><td>: {{ $santri->jenis_kelamin }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">Agama</td><td>: {{ $santri->agama }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">Asal Sekolah</td><td>: {{ $santri->nama_sekolah_asal }}</td></tr>
        </table>

        <h3 class="font-bold text-lg border-b pb-1 mb-4 text-emerald-800">B. Data Orang Tua / Wali</h3>
        <table class="w-full text-sm mb-8">
            <tr><td class="py-2 w-1/3 text-slate-600 font-medium">Nama Ayah / No. WA</td><td class="font-bold">: {{ $santri->nama_ayah }} / {{ $santri->no_wa_ayah }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">Email</td><td class="align-top">: Ayah: {{ $santri->email_ayah }}<br>  Ibu: {{ $santri->email_ibu }}<br>  Wali: {{ $santri->email_wali ?? '-' }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">Nama Ibu / No. WA</td><td>: {{ $santri->nama_ibu }} / {{ $santri->no_wa_ibu ?? '-' }}</td></tr>
            <tr><td class="py-2 text-slate-600 font-medium">Alamat Lengkap</td><td class="align-top">: {{ $santri->alamat_ayah }} RT/RW {{ $santri->rt_rw_ayah }}, Kel. {{ $santri->kelurahan_desa_ayah }}, Kec. {{ $santri->kecamatan_ayah }}</td></tr>
        </table>

        <div class="mt-12 flex justify-between">
            <div class="text-center">
                <p class="text-sm">Panitia Pendataan,</p>
                <br><br><br>
                <p class="font-bold border-b border-black inline-block px-4">( Sistem Terverifikasi )</p>
            </div>
            <div class="text-center">
                <p class="text-sm">Orang Tua / Wali,</p>
                @if($santri->tanda_tangan)
                    <img src="{{ route('pendaftaran.berkas', ['id' => $santri->id, 'field' => 'tanda_tangan']) }}" class="h-20 mx-auto block my-2" alt="Tanda Tangan">
                @else
                    <br><br><br>
                @endif
                <p class="font-bold border-b border-black inline-block px-4">{{ $santri->penandatangan_nama }}</p>
            </div>
        </div>
        
        <p class="text-xs text-center mt-12 text-slate-400 no-print">Dokumen ini sah dan dicetak secara otomatis melalui Sistem Pendataan Cerdas (AI).</p>
    </div>
    <script>
        // Auto print/save on load for convenience (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

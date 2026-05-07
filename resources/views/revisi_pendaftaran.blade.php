<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisi Data Pendaftaran | SPSB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900">
    <main class="max-w-4xl mx-auto px-4 py-10">
        <a href="{{ route('pendaftaran.cek_status') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">Kembali ke Cek Status</a>

        <section class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-sm p-6 md:p-8">
            <p class="text-xs font-extrabold text-emerald-700 uppercase tracking-widest">{{ $santri->nomor_pendaftaran }}</p>
            <h1 class="text-3xl font-extrabold tracking-tight mt-2">Revisi Data Orang Tua</h1>
            <p class="text-slate-500 mt-2">Perbarui data orang tua atau unggah ulang dokumen yang perlu diperbaiki untuk {{ $santri->nama_lengkap }}.</p>

            @if($errors->any())
                <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
                    Mohon periksa kembali data yang ditandai.
                </div>
            @endif

            <form method="POST" action="{{ route('pendaftaran.revisi.update', $santri->revisi_token) }}" enctype="multipart/form-data" class="mt-8 space-y-8">
                @csrf

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h2 class="font-extrabold text-blue-700">Data Bapak</h2>
                        @foreach([
                            'nama_ayah' => 'Nama Bapak',
                            'nik_ayah' => 'NIK Bapak',
                            'no_wa_ayah' => 'No. WhatsApp Bapak',
                            'email_ayah' => 'Email Bapak',
                            'pekerjaan_ayah' => 'Pekerjaan Bapak',
                            'pendidikan_ayah' => 'Pendidikan Bapak',
                        ] as $field => $label)
                            <label class="block">
                                <span class="text-xs font-bold uppercase text-slate-500">{{ $label }}</span>
                                <input name="{{ $field }}" value="{{ old($field, $santri->{$field}) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold focus:border-emerald-500 focus:outline-none">
                                @error($field)<span class="text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <h2 class="font-extrabold text-pink-700">Data Ibu</h2>
                        @foreach([
                            'nama_ibu' => 'Nama Ibu',
                            'nik_ibu' => 'NIK Ibu',
                            'no_wa_ibu' => 'No. WhatsApp Ibu',
                            'email_ibu' => 'Email Ibu',
                            'pekerjaan_ibu' => 'Pekerjaan Ibu',
                            'pendidikan_ibu' => 'Pendidikan Ibu',
                        ] as $field => $label)
                            <label class="block">
                                <span class="text-xs font-bold uppercase text-slate-500">{{ $label }}</span>
                                <input name="{{ $field }}" value="{{ old($field, $santri->{$field}) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold focus:border-emerald-500 focus:outline-none">
                                @error($field)<span class="text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-8">
                    <h2 class="font-extrabold">Unggah Ulang Dokumen</h2>
                    <p class="text-sm text-slate-500 mt-1">Isi hanya dokumen yang diminta revisi atau ingin diganti.</p>
                    <div class="grid md:grid-cols-2 gap-4 mt-5">
                        @foreach([
                            'foto_akta_anak' => 'Akta Kelahiran',
                            'foto_kk' => 'Kartu Keluarga',
                            'foto_ktp_ayah' => 'KTP Bapak',
                            'foto_ktp_ibu' => 'KTP Ibu',
                            'foto_pas_siswa' => 'Foto Anak',
                        ] as $field => $label)
                            <label class="block rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <span class="text-xs font-bold uppercase text-slate-500">{{ $label }}</span>
                                <input type="file" name="{{ $field }}" class="mt-2 w-full text-sm font-semibold text-slate-700">
                                @error($field)<span class="text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                </div>

                <button class="w-full rounded-xl bg-emerald-700 px-6 py-4 font-extrabold text-white hover:bg-emerald-800">KIRIM REVISI</button>
            </form>
        </section>
    </main>
</body>
</html>

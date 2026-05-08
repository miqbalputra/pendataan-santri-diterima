<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sampah Data Peserta Didik | Admin SPSB</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
    </style>
</head>
<body class="text-slate-900 pb-16">
    <header class="bg-slate-950 text-white px-6 py-8">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-rose-300 mb-2">Area Sampah</p>
                <h1 class="text-3xl font-black tracking-tight">Data Peserta Didik di Sampah</h1>
                <p class="mt-2 text-sm font-semibold text-slate-400">Data di sini belum hilang permanen. Pulihkan jika salah hapus, atau hapus permanen dengan password admin.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3 text-sm font-black text-slate-900 shadow-lg hover:bg-emerald-50">
                Kembali ke Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-6 lg:p-10">
        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-100 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="glass-card mb-8 rounded-[2rem] p-6 shadow-xl shadow-slate-900/5">
            <form method="GET" action="{{ route('admin.trash') }}" class="flex flex-col md:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, nomor pendataan, ayah, atau ibu..." class="w-full rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                <button class="rounded-2xl bg-slate-900 px-8 py-3 text-sm font-black text-white hover:bg-emerald-700">Cari</button>
            </form>
        </div>

        <div class="glass-card overflow-hidden rounded-[2rem] shadow-2xl shadow-slate-900/5">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="p-6">Peserta Didik</th>
                            <th class="p-6">Orang Tua</th>
                            <th class="p-6 text-center">Masuk Sampah</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($trashedPendaftar as $santri)
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="p-6">
                                    <div class="font-black text-slate-800">{{ $santri->nama_lengkap }}</div>
                                    <div class="mt-1 text-[10px] font-black uppercase tracking-widest text-emerald-600">{{ $santri->nomor_pendaftaran ?? '-' }}</div>
                                    <div class="mt-1 text-xs font-bold text-slate-400">{{ $santri->nik ?? 'Tanpa NIK' }}</div>
                                </td>
                                <td class="p-6">
                                    <div class="text-xs font-bold text-slate-600">Ayah: {{ $santri->nama_ayah ?? '-' }}</div>
                                    <div class="mt-1 text-xs font-bold text-slate-600">Ibu: {{ $santri->nama_ibu ?? '-' }}</div>
                                </td>
                                <td class="p-6 text-center text-xs font-bold text-slate-500">
                                    {{ optional($santri->deleted_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="p-6">
                                    <div class="flex justify-end gap-2">
                                        <form action="{{ route('admin.trash.restore', $santri->id) }}" method="POST" class="restore-form">
                                            @csrf
                                            <button type="submit" class="rounded-xl border border-emerald-100 bg-emerald-50 px-5 py-2.5 text-xs font-black text-emerald-700 hover:bg-emerald-600 hover:text-white">Pulihkan</button>
                                        </form>
                                        <form action="{{ route('admin.trash.force_delete', $santri->id) }}" method="POST" class="force-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="password" value="">
                                            <button type="submit" class="rounded-xl border border-rose-100 bg-white px-5 py-2.5 text-xs font-black text-rose-600 hover:bg-rose-600 hover:text-white">Hapus Permanen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-20 text-center">
                                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <p class="font-black text-slate-700">Sampah kosong.</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-400">Belum ada data peserta didik yang dihapus sementara.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($trashedPendaftar->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/50 p-6">
                    {{ $trashedPendaftar->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    </main>

    <script>
        document.querySelectorAll('.restore-form').forEach((form) => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                Swal.fire({
                    title: 'Pulihkan data ini?',
                    text: 'Data akan kembali muncul di dashboard admin.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, pulihkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                    customClass: { popup: 'rounded-[2rem]' }
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        document.querySelectorAll('.force-delete-form').forEach((form) => {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const result = await Swal.fire({
                    title: 'Hapus permanen?',
                    html: 'Tindakan ini akan menghapus data dan file upload secara permanen.<br><strong>Masukkan password admin untuk melanjutkan.</strong>',
                    icon: 'error',
                    input: 'password',
                    inputPlaceholder: 'Password admin',
                    inputAttributes: { autocapitalize: 'off', autocomplete: 'current-password' },
                    showCancelButton: true,
                    confirmButtonText: 'Hapus Permanen',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    customClass: { popup: 'rounded-[2rem]' },
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password admin wajib diisi.');
                            return false;
                        }
                        return password;
                    }
                });

                if (result.isConfirmed) {
                    form.querySelector('input[name="password"]').value = result.value;
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>

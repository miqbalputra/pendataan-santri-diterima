<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
    <div class="p-8">
        <h1 class="text-2xl font-bold mb-6">Rekap Pendaftaran Santri Baru</h1>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="p-4">Nama</th>
                        <th class="p-4">Ortu</th>
                        <th class="p-4">WA Ortu</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendaftar as $p)
                    <tr class="border-b">
                        <td class="p-4 font-bold">{{ $p->nama_lengkap }}</td>
                        <td class="p-4">{{ $p->nama_ayah }}</td>
                        <td class="p-4">{{ $p->no_wa_ayah }}</td>
                        <td class="p-4"><span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs">{{ $p->status_pendaftaran }}</span></td>
                        <td class="p-4"><a href="#" class="text-blue-600">Lihat Detail</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

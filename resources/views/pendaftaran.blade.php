<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Santri - Tunas Ilmu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="max-w-3xl mx-auto py-12 px-6">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="bg-emerald-600 p-8 text-white">
                <h1 class="text-2xl font-bold">Formulir Pendaftaran Online</h1>
                <p class="text-emerald-100">Silakan isi data calon santri dengan lengkap.</p>
            </div>
            
            <form action="/pendaftaran" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                @csrf
                
                <div class="space-y-4">
                    <h2 class="font-bold text-lg text-emerald-700">A. Identitas Anak</h2>
                    <input type="text" name="nama_lengkap" placeholder="Nama Lengkap Anak" class="w-full border p-3 rounded-lg" required>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="nik" placeholder="NIK Anak" class="border p-3 rounded-lg" required>
                        <select name="jenis_kelamin" class="border p-3 rounded-lg">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="font-bold text-lg text-emerald-700">B. Upload Dokumen (Foto)</h2>
                    <div class="grid grid-cols-2 gap-6 text-sm">
                        <div>
                            <label class="block mb-1">KTP Bapak</label>
                            <input type="file" name="foto_ktp_ayah" class="w-full" required>
                        </div>
                        <div>
                            <label class="block mb-1">KTP Ibu</label>
                            <input type="file" name="foto_ktp_ibu" class="w-full" required>
                        </div>
                        <div>
                            <label class="block mb-1">Akta Anak</label>
                            <input type="file" name="foto_akta_anak" class="w-full" required>
                        </div>
                        <div>
                            <label class="block mb-1">Kartu Keluarga (KK)</label>
                            <input type="file" name="foto_kk" class="w-full" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-emerald-600 text-white py-4 rounded-xl font-bold text-lg">Kirim Pendaftaran</button>
            </form>
        </div>
    </div>
</body>
</html>

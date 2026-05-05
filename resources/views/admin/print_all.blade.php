<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Pendaftar SPSB</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 20px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <button onclick="window.close()">Tutup</button>
    </div>
    
    <h1>Laporan Data Pendaftar SPSB</h1>
    <p>Dicetak pada: {{ date('d-m-Y H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Anak</th>
                <th>NIK Anak</th>
                <th>Nama Ayah</th>
                <th>Kontak Ayah</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->nama_lengkap }}</td>
                <td>{{ $row->nik_anak }}</td>
                <td>{{ $row->nama_ayah }}</td>
                <td>{{ $row->no_wa_ayah }}</td>
                <td>{{ $row->status_pendaftaran }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

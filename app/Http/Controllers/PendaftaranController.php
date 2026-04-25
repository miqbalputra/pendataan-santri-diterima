<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PendaftaranController extends Controller
{
    public function index() {
        return view('pendaftaran');
    }

    public function store(Request $request) {
        $data = $request->all();

        // Upload Dokumen ke Storage VPS
        $docs = ['foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk'];
        foreach ($docs as $doc) {
            if ($request->hasFile($doc)) {
                $path = $request->file($doc)->store('pendaftaran', 'public');
                $data[$doc] = $path;
            }
        }

        $santri = CalonSantri::create($data);

        // TRIGGER n8n WEBHOOK
        // Silakan ganti URL di bawah dengan URL Webhook n8n Anda di Coolify
        try {
            Http::post('https://n8n.griyaquran.web.id/webhook/pendaftaran-baru', [
                'nama_santri' => $santri->nama_lengkap,
                'nama_ayah'   => $santri->nama_ayah,
                'no_wa'       => $santri->no_wa_ayah,
                'email'       => $request->email ?? '-',
                'group_link'  => 'https://chat.whatsapp.com/LnkGrupKelas'
            ]);
        } catch (\Exception $e) {
            // Log error jika n8n gagal, tapi pendaftaran tetap tersimpan
        }

        return response()->json(['message' => 'Alhamdulillah, data pendaftaran telah kami terima!']);
    }
}

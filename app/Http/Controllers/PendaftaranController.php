<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PendaftaranController extends Controller
{
    public function index() {
        $app_locked = Setting::where('key', 'app_locked')->value('value') == '1';
        if ($app_locked) {
            return view('locked');
        }

        $ocr_engine = Setting::where('key', 'ocr_engine')->value('value') ?? 'local';
        $ocr_webhook_url = Setting::where('key', 'ocr_webhook_url')->value('value') ?? 'https://n8n.griyaquran.web.id/webhook/ocr-ktp';
        return view('pendaftaran', compact('ocr_engine', 'ocr_webhook_url'));
    }

    public function store(Request $request) {
        $data = $request->all();

        // Upload Dokumen ke Storage VPS
        $docs = ['foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk', 'foto_pas_siswa'];
        foreach ($docs as $doc) {
            if ($request->hasFile($doc)) {
                $path = $request->file($doc)->store('pendaftaran', 'public');
                $data[$doc] = $path;
            }
        }

        // Tanda tangan base64
        if ($request->filled('tanda_tangan_base64')) {
            $image_parts = explode(";base64,", $request->tanda_tangan_base64);
            if(count($image_parts) == 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'pendaftaran/ttd_' . uniqid() . '.png';
                Storage::disk('public')->put($fileName, $image_base64);
                $data['tanda_tangan'] = $fileName;
            }
        }

        // Hapus field non-database sebelum mass assignment
        unset($data['tanda_tangan_base64']);

        $santri = CalonSantri::create($data);

        ActivityLog::create([
            'aktivitas' => "Pendaftaran Baru: {$santri->nama_lengkap}",
            'aktor' => "Calon Peserta Didik",
            'ip_address' => $request->ip()
        ]);

        // TRIGGER n8n WEBHOOK NOTIFIKASI
        // Webhook ini bertugas men-generate PDF dan mengirim WA/Email
        try {
            Http::post('https://n8n.griyaquran.web.id/webhook/pendaftaran-baru', [
                'santri_id'   => $santri->id,
                'nama_santri' => $santri->nama_lengkap,
                'nama_ayah'   => $santri->nama_ayah,
                'no_wa'       => $santri->no_wa_ayah,
                'email'       => $santri->email_orangtua,
                'nik_anak'    => $santri->nik_anak,
                'alamat'      => $santri->alamat_ayah,
                'kelurahan'   => $santri->kelurahan_desa_ayah,
                'kecamatan'   => $santri->kecamatan_ayah,
                'sekolah_asal'=> $santri->nama_sekolah_asal,
                'waktu_daftar'=> $santri->created_at->format('d-m-Y H:i:s'),
                'group_link'  => 'https://chat.whatsapp.com/GrupSPSB2025' // Link Grup Wali Peserta Didik
            ]);
        } catch (\Exception $e) {
            // Log error jika n8n gagal
        }

        return response()->json(['message' => 'Alhamdulillah, data pendaftaran telah kami terima!']);
    }

    public function uploadOcr(Request $request) {
        $request->validate([
            'file' => 'required|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'target' => 'required'
        ]);

        $file = $request->file('file');
        $engine = Setting::where('key', 'ocr_engine')->value('value') ?? 'local';

        // MODE DIRECT AI
        if ($engine == 'direct_ai') {
            $endpoint = Setting::where('key', 'ai_endpoint')->value('value') ?? 'https://api.openai.com/v1/chat/completions';
            $api_key = Setting::where('key', 'ai_api_key')->value('value');
            $model = Setting::where('key', 'ai_model')->value('value') ?? 'gpt-4o';
            
            if (!str_ends_with($endpoint, '/chat/completions')) {
                $endpoint = rtrim($endpoint, '/') . '/chat/completions';
            }

            if(!$api_key) {
                return response()->json(['error' => 'API Key belum dikonfigurasi di dashboard admin.'], 500);
            }

            try {
                // Kompresi dan Resize Gambar menggunakan GD sebelum dikirim ke API
                $sourcePath = $file->getRealPath();
                $info = @getimagesize($sourcePath);
                
                $base64Image = '';
                $mimeType = 'image/jpeg'; // Paksa ke JPEG
                
                if ($info) {
                    $mime = $info['mime'];
                    $image = null;
                    if ($mime == 'image/jpeg') $image = @imagecreatefromjpeg($sourcePath);
                    elseif ($mime == 'image/png') $image = @imagecreatefrompng($sourcePath);
                    elseif ($mime == 'image/webp') $image = @imagecreatefromwebp($sourcePath);
                    
                    if ($image) {
                        $maxWidth = 1200;
                        $width = imagesx($image);
                        $height = imagesy($image);
                        
                        if ($width > $maxWidth || $height > $maxWidth) {
                            $ratio = $width / $height;
                            if ($ratio > 1) {
                                $newWidth = $maxWidth;
                                $newHeight = $maxWidth / $ratio;
                            } else {
                                $newWidth = $maxWidth * $ratio;
                                $newHeight = $maxWidth;
                            }
                            $newImage = imagecreatetruecolor(intval($newWidth), intval($newHeight));
                            
                            // Handle transparency
                            $bg = imagecolorallocate($newImage, 255, 255, 255);
                            imagefill($newImage, 0, 0, $bg);
                            
                            imagecopyresampled($newImage, $image, 0, 0, 0, 0, intval($newWidth), intval($newHeight), $width, $height);
                            $image = $newImage;
                        }
                        
                        ob_start();
                        imagejpeg($image, null, 70); // 70% quality untuk menekan size
                        $image_data = ob_get_clean();
                        imagedestroy($image);
                        
                        $base64Image = base64_encode($image_data);
                    } else {
                        // Fallback jika format tidak didukung GD
                        $base64Image = base64_encode(file_get_contents($sourcePath));
                        $mimeType = $file->getMimeType();
                    }
                } else {
                    $base64Image = base64_encode(file_get_contents($sourcePath));
                    $mimeType = $file->getMimeType();
                }

                $response = Http::withToken($api_key)->timeout(60)->post($endpoint, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text', 
                                    'text' => 'Anda adalah asisten ekstraksi data dokumen. Baca dokumen ini (KTP/KK/Akta) dan KEMBALIKAN HANYA TEKS dengan format baku berikut ini (tanpa markdown, tanpa tambahan apapun). Jika data tidak ada, kosongkan saja nilainya:
NAMA : [Nama Lengkap]
NIK : [Nomor Induk Kependudukan 16 digit jika ada]
Lahir : [Tempat Lahir], [DD-MM-YYYY]
ALAMAT : [Nama Jalan/Dusun]
RT/RW : [Nomor RT]/[Nomor RW]
KEL/DESA : [Nama Kelurahan atau Desa]
KECAMATAN : [Nama Kecamatan]
PEKERJAAN : [Pekerjaan]'
                                ],
                                [
                                    'type' => 'image_url', 
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$base64Image}"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 1500
                ]);

                if ($response->successful()) {
                    $text = $response->json('choices.0.message.content') ?? '';
                    return response()->json([
                        'message' => 'OCR Direct AI Berhasil',
                        'extracted_text' => $text
                    ]);
                } else {
                    return response()->json(['error' => 'Gagal memproses via Direct AI: ' . $response->body()], 500);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Terjadi kesalahan Direct AI: ' . $e->getMessage()], 500);
            }
        }

        // MODE N8N
        if ($engine == 'n8n') {
            $webhook_url = Setting::where('key', 'ocr_webhook_url')->value('value');
            if(!$webhook_url) {
                return response()->json(['error' => 'Webhook URL n8n belum dikonfigurasi di dashboard admin.'], 500);
            }

            try {
                $response = Http::attach(
                    'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
                )->post($webhook_url);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $text = '';
                    if(isset($responseData['text'])) {
                        $text = $responseData['text'];
                    } elseif (isset($responseData[0]['text'])) {
                        $text = $responseData[0]['text'];
                    } else {
                        $text = json_encode($responseData);
                    }
                    
                    return response()->json([
                        'message' => 'OCR n8n Berhasil',
                        'extracted_text' => $text
                    ]);
                } else {
                    return response()->json(['error' => 'Gagal terhubung ke n8n OCR. HTTP Code: ' . $response->status()], 500);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Terjadi kesalahan sistem OCR n8n: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'Engine OCR tidak didukung atau menggunakan mode lokal.'], 400);
    }
    public function cetak($id) {
        $santri = CalonSantri::findOrFail($id);
        
        $kop = [
            'baris_1' => Setting::where('key', 'kop_baris_1')->value('value') ?? 'GRIYA QUR\'AN BAITUL MANSHURIN',
            'baris_2' => Setting::where('key', 'kop_baris_2')->value('value') ?? 'Sistem Pendaftaran Peserta Didik Baru (SPSB)',
            'baris_3' => Setting::where('key', 'kop_baris_3')->value('value') ?? 'Jl. Contoh No. 123, Kota ABC, Propinsi XYZ | Telp: 0812-3456-7890',
        ];

        return view('pendaftaran_pdf', compact('santri', 'kop'));
    }
}
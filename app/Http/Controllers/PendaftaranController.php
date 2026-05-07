<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $this->validatePendaftaran($request);

        $data = $request->only((new CalonSantri())->getFillable());

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
        
        // Pastikan checkbox pernyataan diubah ke boolean (1/0) bukannya string 'on'
        $data['pernyataan_kebenaran_data'] = $request->has('pernyataan_kebenaran_data');
        $data['status_pendaftaran'] = $data['status_pendaftaran'] ?? 'Pending';
        $santri = DB::transaction(function () use ($data, $request) {
            $santri = CalonSantri::create($data);

            ActivityLog::create([
                'aktivitas' => "Pendaftaran Baru: {$santri->nama_lengkap}",
                'aktor' => "Calon Peserta Didik",
                'ip_address' => $request->ip()
            ]);

            return $santri;
        });

        $this->sendPendaftaranNotifications($santri, $request);

        return redirect('/pendaftaran/sukses')->with('nama_santri', $santri->nama_lengkap);
    }

    private function sendPendaftaranNotifications(CalonSantri $santri, Request $request): void
    {
        $payload = $this->buildNotificationPayload($santri);

        $webhooks = [
            'email' => Setting::where('key', 'n8n_email_webhook_url')->value('value'),
            'whatsapp' => Setting::where('key', 'n8n_whatsapp_webhook_url')->value('value'),
        ];

        foreach ($webhooks as $channel => $webhookUrl) {
            if (!$webhookUrl) {
                continue;
            }

            try {
                $response = Http::timeout(20)->post($webhookUrl, $payload);

                if ($response->failed()) {
                    ActivityLog::create([
                        'aktivitas' => "Webhook {$channel} gagal HTTP {$response->status()}: {$santri->nama_lengkap}",
                        'aktor' => 'Sistem',
                        'ip_address' => $request->ip()
                    ]);
                }
            } catch (\Exception $e) {
                ActivityLog::create([
                    'aktivitas' => "Webhook {$channel} gagal: {$santri->nama_lengkap}",
                    'aktor' => 'Sistem',
                    'ip_address' => $request->ip()
                ]);
            }
        }
    }

    private function buildNotificationPayload(CalonSantri $santri): array
    {
        $isIkhwan = $santri->jenis_kelamin === 'Laki-laki';
        $kelas = $isIkhwan ? 'ikhwan' : 'akhwat';
        $groupLink = Setting::where('key', $isIkhwan ? 'group_ikhwan_url' : 'group_akhwat_url')->value('value') ?? '';
        $groupRule = $isIkhwan
            ? 'Group kelas ikhwan boleh diisi oleh bapak dan ibu.'
            : 'Group kelas akhwat hanya boleh diisi oleh ibu.';

        $emailRecipients = collect([
            ['role' => 'ayah', 'name' => $santri->nama_ayah, 'email' => $santri->email_ayah],
            ['role' => 'ibu', 'name' => $santri->nama_ibu, 'email' => $santri->email_ibu],
        ])->filter(fn ($recipient) => !empty($recipient['email']))->unique('email')->values()->all();

        $whatsappRecipients = collect($isIkhwan ? [
            ['role' => 'ayah', 'name' => $santri->nama_ayah, 'phone' => $santri->no_wa_ayah],
            ['role' => 'ibu', 'name' => $santri->nama_ibu, 'phone' => $santri->no_wa_ibu],
        ] : [
            ['role' => 'ibu', 'name' => $santri->nama_ibu, 'phone' => $santri->no_wa_ibu],
        ])->filter(fn ($recipient) => !empty($recipient['phone']))->values()->all();

        return [
            'event' => 'pendaftaran_baru',
            'santri' => [
                'id' => $santri->id,
                'nomor_pendaftaran' => 'SPSB-' . str_pad((string) $santri->id, 5, '0', STR_PAD_LEFT),
                'nama_lengkap' => $santri->nama_lengkap,
                'nik' => $santri->nik,
                'jenis_kelamin' => $santri->jenis_kelamin,
                'kelas' => $kelas,
                'tempat_lahir' => $santri->tempat_lahir,
                'tanggal_lahir' => $santri->tanggal_lahir ? \Carbon\Carbon::parse($santri->tanggal_lahir)->format('Y-m-d') : null,
                'sekolah_asal' => $santri->nama_sekolah_asal,
                'status_pendaftaran' => $santri->status_pendaftaran,
                'waktu_daftar' => $santri->created_at->format('Y-m-d H:i:s'),
            ],
            'orang_tua' => [
                'ayah' => [
                    'nama' => $santri->nama_ayah,
                    'no_wa' => $santri->no_wa_ayah,
                    'email' => $santri->email_ayah,
                ],
                'ibu' => [
                    'nama' => $santri->nama_ibu,
                    'no_wa' => $santri->no_wa_ibu,
                    'email' => $santri->email_ibu,
                ],
            ],
            'alamat' => [
                'alamat_lengkap' => $santri->alamat_lengkap,
                'rt_rw' => $santri->rt_rw,
                'kelurahan_desa' => $santri->kelurahan_desa,
                'kecamatan' => $santri->kecamatan,
                'kabupaten_kota' => $santri->kabupaten_kota,
                'propinsi' => $santri->propinsi,
                'kode_pos' => $santri->kode_pos,
            ],
            'ringkasan' => [
                'judul' => 'Ringkasan Pendaftaran SPSB',
                'baris' => [
                    'Nomor Pendaftaran' => 'SPSB-' . str_pad((string) $santri->id, 5, '0', STR_PAD_LEFT),
                    'Nama Santri' => $santri->nama_lengkap,
                    'NIK' => $santri->nik,
                    'Jenis Kelamin' => $santri->jenis_kelamin,
                    'Status' => $santri->status_pendaftaran,
                    'Waktu Daftar' => $santri->created_at->format('d-m-Y H:i:s'),
                ],
            ],
            'group' => [
                'kelas' => $kelas,
                'link' => $groupLink,
                'aturan' => $groupRule,
            ],
            'recipients' => [
                'email' => $emailRecipients,
                'whatsapp' => $whatsappRecipients,
            ],
            'links' => [
                'cetak' => route('pendaftaran.cetak', $santri->id),
                'admin_detail' => route('admin.show', $santri->id),
            ],
        ];
    }

    private function validatePendaftaran(Request $request): void
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'nik' => 'required|string|size:16',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'agama' => 'required|string|max:100',
            'alamat_lengkap' => 'required|string',
            'rt_rw' => 'required|string|max:20',
            'kelurahan_desa' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'propinsi' => 'required|string|max:255',
            'kode_pos' => 'required|string|max:20',
            'jenis_tinggal' => 'required|string|max:255',
            'alat_transportasi' => 'required|string|max:255',
            'nama_ayah' => 'required|string|max:255',
            'nik_ayah' => 'required|string|size:16',
            'no_wa_ayah' => 'required|string|max:25',
            'nama_ibu' => 'required|string|max:255',
            'nik_ibu' => 'required|string|size:16',
            'no_wa_ibu' => 'required|string|max:25',
            'penandatangan_nama' => 'required|string|max:255',
            'pernyataan_kebenaran_data' => 'accepted',
            'foto_akta_anak' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'foto_kk' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'foto_ktp_ayah' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_ktp_ibu' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_pas_siswa' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'tanda_tangan_base64' => 'nullable|string',
        ]);
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

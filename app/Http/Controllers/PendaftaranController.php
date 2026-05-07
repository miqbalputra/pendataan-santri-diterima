<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\NotificationLog;
use App\Models\Periode;
use App\Models\Gelombang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $existingColumns = Schema::getColumnListing('calon_santris');
        $hasColumn = fn (string $column): bool => in_array($column, $existingColumns, true);
        $data = array_intersect_key(
            $request->only((new CalonSantri())->getFillable()),
            array_flip($existingColumns)
        );

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
        $activeGelombang = (Schema::hasTable('gelombangs') && $hasColumn('gelombang_id'))
            ? Gelombang::where('is_active', true)->first()
            : null;
        if ($activeGelombang && $activeGelombang->kuota && CalonSantri::where('gelombang_id', $activeGelombang->id)->count() >= $activeGelombang->kuota) {
            return back()->withInput()->withErrors([
                'gelombang' => "Kuota {$activeGelombang->nama_gelombang} sudah penuh. Silakan hubungi panitia.",
            ]);
        }

        if (Schema::hasTable('periodes') && $hasColumn('periode_id')) {
            $data['periode_id'] = Periode::where('is_active', true)->value('id');
        }

        if ($hasColumn('gelombang_id')) {
            $data['gelombang_id'] = $activeGelombang?->id;
        }

        if ($hasColumn('dokumen_status')) {
            $data['dokumen_status'] = $this->initialDocumentStatuses($data);
        }

        if ($hasColumn('revisi_token')) {
            $data['revisi_token'] = Str::random(48);
        }

        $santri = DB::transaction(function () use ($data, $request, $hasColumn) {
            $santri = CalonSantri::create($data);

            if ($hasColumn('nomor_pendaftaran')) {
                $santri->forceFill([
                    'nomor_pendaftaran' => $this->generateNomorPendaftaran($santri),
                ])->save();
            }

            if (Schema::hasTable('activity_logs')) {
                ActivityLog::create([
                    'aktivitas' => "Pendataan Baru: {$santri->nama_lengkap}",
                    'aktor' => "Orang Tua / Wali",
                    'ip_address' => $request->ip()
                ]);
            }

            return $santri;
        });

        try {
            $this->sendPendaftaranNotifications($santri, $request);
        } catch (\Throwable $e) {
            Log::warning('Notifikasi pendataan gagal setelah data tersimpan.', [
                'calon_santri_id' => $santri->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect('/pendaftaran/sukses')
            ->with('nama_santri', $santri->nama_lengkap)
            ->with('santri_id', $santri->id)
            ->with('nomor_pendaftaran', $santri->nomor_pendaftaran ?? $this->generateNomorPendaftaran($santri));
    }

    private function sendPendaftaranNotifications(CalonSantri $santri, Request $request): void
    {
        $payload = $this->buildNotificationPayload($santri);

        $webhooks = [
            'email' => Setting::where('key', 'n8n_email_webhook_url')->value('value'),
            'whatsapp' => Setting::where('key', 'n8n_whatsapp_webhook_url')->value('value'),
        ];

        foreach ($webhooks as $channel => $webhookUrl) {
            if (empty($payload['recipients'][$channel])) {
                $this->recordNotificationLog($santri, $channel, $payload, 'skipped', null, 'Tidak ada penerima untuk channel ini.');
                continue;
            }

            if (!$webhookUrl) {
                $this->recordNotificationLog($santri, $channel, $payload, 'skipped', null, 'Webhook belum dikonfigurasi.');
                continue;
            }

            try {
                $response = Http::timeout(20)->post($webhookUrl, $payload);
                $this->recordNotificationLog(
                    $santri,
                    $channel,
                    $payload,
                    $response->successful() ? 'success' : 'failed',
                    $response->status(),
                    $response->successful() ? 'Webhook terkirim.' : $response->body()
                );

                if ($response->failed() && Schema::hasTable('activity_logs')) {
                    ActivityLog::create([
                        'aktivitas' => "Webhook {$channel} gagal HTTP {$response->status()}: {$santri->nama_lengkap}",
                        'aktor' => 'Sistem',
                        'ip_address' => $request->ip()
                    ]);
                }
            } catch (\Exception $e) {
                $this->recordNotificationLog($santri, $channel, $payload, 'failed', null, $e->getMessage());
                if (Schema::hasTable('activity_logs')) {
                    ActivityLog::create([
                        'aktivitas' => "Webhook {$channel} gagal: {$santri->nama_lengkap}",
                        'aktor' => 'Sistem',
                        'ip_address' => $request->ip()
                    ]);
                }
            }
        }
    }

    private function buildNotificationPayload(CalonSantri $santri): array
    {
        $isIkhwan = $santri->jenis_kelamin === 'Laki-laki';
        $kelas = $isIkhwan ? 'ikhwan' : 'akhwat';
        $kategoriKelamin = $isIkhwan ? 'putra' : 'putri';
        $groupLink = Setting::where('key', $isIkhwan ? 'group_ikhwan_url' : 'group_akhwat_url')->value('value') ?? '';
        $groupRule = $isIkhwan
            ? 'Peserta didik putra masuk grup kelas ikhwan. Nomor WhatsApp bapak dan ibu mendapat pesan supaya masuk grup kelas ikhwan.'
            : 'Peserta didik putri masuk grup kelas akhwat. Hanya nomor WhatsApp ibu yang mendapat pesan supaya masuk grup kelas akhwat.';

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
            'event_label' => 'Pendataan Baru',
            'santri' => [
                'id' => $santri->id,
                'nomor_pendaftaran' => $santri->nomor_pendaftaran,
                'nomor_pendataan' => $santri->nomor_pendaftaran,
                'nama_lengkap' => $santri->nama_lengkap,
                'nik' => $santri->nik,
                'jenis_kelamin' => $santri->jenis_kelamin,
                'kelas' => $kelas,
                'kategori_kelamin' => $kategoriKelamin,
                'tempat_lahir' => $santri->tempat_lahir,
                'tanggal_lahir' => $santri->tanggal_lahir ? \Carbon\Carbon::parse($santri->tanggal_lahir)->format('Y-m-d') : null,
                'sekolah_asal' => $santri->nama_sekolah_asal,
                'status_pendaftaran' => $santri->status_pendaftaran,
                'waktu_daftar' => $santri->created_at->format('Y-m-d H:i:s'),
                'waktu_data_masuk' => $santri->created_at->format('Y-m-d H:i:s'),
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
                'judul' => 'Ringkasan Pendataan SPSB',
                'baris' => [
                    'Nomor Pendataan' => $santri->nomor_pendaftaran,
                    'Nama Peserta Didik' => $santri->nama_lengkap,
                    'NIK' => $santri->nik,
                    'Jenis Kelamin' => $santri->jenis_kelamin,
                    'Status Verifikasi Data' => $santri->status_pendaftaran === 'Diterima' ? 'Data Lengkap / Terverifikasi' : ($santri->status_pendaftaran === 'Ditolak' ? 'Data Tidak Valid / Tidak Dilanjutkan' : 'Menunggu Verifikasi Data'),
                    'Waktu Data Masuk' => $santri->created_at->format('d-m-Y H:i:s'),
                ],
            ],
            'group' => [
                'kelas' => $kelas,
                'kategori_kelamin' => $kategoriKelamin,
                'link' => $groupLink,
                'aturan' => $groupRule,
                'penerima_whatsapp' => $isIkhwan ? ['ayah', 'ibu'] : ['ibu'],
            ],
            'recipients' => [
                'email' => $emailRecipients,
                'whatsapp' => $whatsappRecipients,
            ],
            'links' => [
                'cetak' => route('pendaftaran.cetak', $santri->id),
                'admin_detail' => route('admin.show', $santri->id),
                'cek_status' => route('pendaftaran.cek_status'),
                'revisi' => route('pendaftaran.revisi', $santri->revisi_token),
            ],
        ];
    }

    public function cekStatus()
    {
        return view('cek_status');
    }

    public function cariStatus(Request $request)
    {
        $request->validate([
            'kata_kunci' => 'required|string|max:255',
        ]);

        $keyword = trim($request->kata_kunci);
        $santri = CalonSantri::where('nomor_pendaftaran', $keyword)
            ->orWhere('nik', $keyword)
            ->first();

        return view('cek_status', compact('santri', 'keyword'));
    }

    public function editRevisi(string $token)
    {
        $santri = CalonSantri::where('revisi_token', $token)->firstOrFail();

        return view('revisi_pendaftaran', compact('santri'));
    }

    public function updateRevisi(Request $request, string $token)
    {
        $santri = CalonSantri::where('revisi_token', $token)->firstOrFail();

        $request->validate([
            'nama_ayah' => 'required|string|max:255',
            'nik_ayah' => 'required|digits:16',
            'no_wa_ayah' => ['required', 'string', 'max:25', 'regex:/^(08|628)[0-9]{8,13}$/'],
            'email_ayah' => 'nullable|email|max:255',
            'pekerjaan_ayah' => 'nullable|string|max:255',
            'pendidikan_ayah' => 'nullable|string|max:255',
            'nama_ibu' => 'required|string|max:255',
            'nik_ibu' => 'required|digits:16',
            'no_wa_ibu' => ['required', 'string', 'max:25', 'regex:/^(08|628)[0-9]{8,13}$/'],
            'email_ibu' => 'nullable|email|max:255',
            'pekerjaan_ibu' => 'nullable|string|max:255',
            'pendidikan_ibu' => 'nullable|string|max:255',
            'foto_akta_anak' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'foto_kk' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'foto_ktp_ayah' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_ktp_ibu' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_pas_siswa' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $fields = [
            'nama_ayah', 'nik_ayah', 'no_wa_ayah', 'email_ayah', 'pekerjaan_ayah', 'pendidikan_ayah',
            'nama_ibu', 'nik_ibu', 'no_wa_ibu', 'email_ibu', 'pekerjaan_ibu', 'pendidikan_ibu',
        ];
        $data = $request->only($fields);
        $documentStatuses = $santri->dokumen_status ?? [];

        foreach (['foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk', 'foto_pas_siswa'] as $doc) {
            if ($request->hasFile($doc)) {
                $data[$doc] = $request->file($doc)->store('pendaftaran', 'public');
                $documentStatuses[$doc] = 'menunggu_review';
            }
        }

        $data['dokumen_status'] = $documentStatuses;
        $data['revisi_selesai_pada'] = now();
        $santri->update($data);

        ActivityLog::create([
            'aktivitas' => "Revisi Data Orang Tua: {$santri->nama_lengkap}",
            'aktor' => 'Orang Tua',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('pendaftaran.cek_status')
            ->with('success', 'Revisi data berhasil dikirim. Panitia akan meninjau kembali data Anda.');
    }

    private function initialDocumentStatuses(array $data): array
    {
        $statuses = [];
        foreach (['foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk', 'foto_pas_siswa'] as $doc) {
            $statuses[$doc] = empty($data[$doc]) ? 'kosong' : 'menunggu_review';
        }

        return $statuses;
    }

    private function generateNomorPendaftaran(CalonSantri $santri): string
    {
        $year = optional($santri->created_at)->format('Y') ?: now()->format('Y');

        return 'SPSB-' . $year . '-' . str_pad((string) $santri->id, 5, '0', STR_PAD_LEFT);
    }

    private function recordNotificationLog(CalonSantri $santri, string $channel, array $payload, string $status, ?int $httpStatus, ?string $message): void
    {
        if (!Schema::hasTable('notification_logs')) {
            Log::info('Log notifikasi dilewati karena tabel notification_logs belum tersedia.', [
                'calon_santri_id' => $santri->id,
                'channel' => $channel,
                'status' => $status,
            ]);
            return;
        }

        $recipients = collect($payload['recipients'][$channel] ?? []);
        $recipientSummary = $recipients->map(fn ($item) => $item['email'] ?? $item['phone'] ?? null)->filter()->implode(', ');
        $roleSummary = $recipients->pluck('role')->filter()->implode(', ');

        NotificationLog::create([
            'calon_santri_id' => $santri->id,
            'channel' => $channel,
            'recipient' => $recipientSummary,
            'recipient_role' => $roleSummary,
            'status' => $status,
            'http_status' => $httpStatus,
            'message' => $message ? Str::limit($message, 500) : null,
            'payload' => $payload,
        ]);
    }

    private function validatePendaftaran(Request $request): void
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'nik' => 'required|digits:16|unique:calon_santris,nik',
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
            'nik_ayah' => 'required|digits:16',
            'no_wa_ayah' => ['required', 'string', 'max:25', 'regex:/^(08|628)[0-9]{8,13}$/'],
            'nama_ibu' => 'required|string|max:255',
            'nik_ibu' => 'required|digits:16',
            'no_wa_ibu' => ['required', 'string', 'max:25', 'regex:/^(08|628)[0-9]{8,13}$/'],
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

                $prompt = $this->buildDirectAiOcrPrompt($request->target);

                $response = Http::withToken($api_key)->timeout(60)->post($endpoint, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text', 
                                    'text' => $prompt
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
                    $fields = $this->extractOcrJsonFields($text);

                    return response()->json([
                        'message' => 'OCR Direct AI Berhasil',
                        'extracted_text' => $text,
                        'fields' => $fields,
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

    private function buildDirectAiOcrPrompt(string $target): string
    {
        $base = 'Anda adalah mesin ekstraksi data dokumen Indonesia untuk formulir SPSB. Baca gambar dengan teliti, lalu kembalikan HANYA JSON valid tanpa markdown, tanpa komentar, tanpa teks tambahan. Jika data tidak terlihat, isi string kosong. Jangan menebak. Bersihkan hasil dari label seperti NIK, Nama, Tempat/Tgl Lahir, Jenis Kelamin, Gol. Darah, dan tanda baca yang bukan bagian data. Tanggal wajib format YYYY-MM-DD. Nama wajib nama manusia saja, bukan label berikutnya.';

        $schema = 'Gunakan schema ini: {"nama":"","nik":"","tempat_lahir":"","tanggal_lahir":"","jenis_kelamin":"","agama":"","alamat":"","rt_rw":"","kelurahan_desa":"","kecamatan":"","pekerjaan":"","pendidikan":"","penghasilan":"","nama_ayah":"","nama_ibu":""}.';

        if ($target === 'akta') {
            return $base . "\nDokumen target: AKTA KELAHIRAN anak. Prioritas utama adalah data ANAK, bukan ayah/ibu/pejabat. Ambil nama anak dari kalimat seperti 'anak ... bernama ...' atau nama utama setelah tanggal lahir. Ambil NIK anak dari Nomor Induk Kependudukan jika terlihat. Ambil tempat lahir dan tanggal lahir anak dari narasi kelahiran. Jika nama ayah/ibu tertulis jelas, isi nama_ayah dan nama_ibu, tetapi jangan jadikan nama ayah/ibu sebagai nama anak.\n" . $schema;
        }

        if ($target === 'ayah') {
            return $base . "\nDokumen target: KTP BAPAK/AYAH. Ambil hanya data pemilik KTP pada dokumen ini. Nama harus persis nilai setelah label Nama, contoh 'SULISTYONO', bukan 'SULISTYONO NIK'. Jangan isi nama anak atau nama ibu dari dokumen ini.\n" . $schema;
        }

        if ($target === 'ibu') {
            return $base . "\nDokumen target: KTP IBU. Ambil hanya data pemilik KTP pada dokumen ini. Nama harus persis nilai setelah label Nama, contoh 'SULISTYONO', bukan 'SULISTYONO NIK'. Jangan isi nama anak atau nama ayah dari dokumen ini.\n" . $schema;
        }

        if ($target === 'kk') {
            return $base . "\nDokumen target: KARTU KELUARGA. Prioritas ambil alamat keluarga dan data anggota keluarga jika sangat jelas. Jangan mengisi nama anak/ayah/ibu bila tidak yakin dari baris hubungan keluarga. Untuk nama ayah gunakan baris Kepala Keluarga atau kolom Ayah yang sesuai anak. Untuk nama ibu gunakan kolom Ibu yang sesuai anak.\n" . $schema;
        }

        return $base . "\nDokumen target tidak dikenal. Ekstrak data yang terlihat dengan hati-hati.\n" . $schema;
    }

    private function extractOcrJsonFields(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);

        if (preg_match('/\{.*\}/s', $clean, $match)) {
            $clean = $match[0];
        }

        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->mapWithKeys(fn ($value, $key) => [$key => is_scalar($value) ? trim((string) $value) : ''])
            ->all();
    }

    public function cetak($id) {
        $santri = CalonSantri::findOrFail($id);
        
        $kop = [
            'baris_1' => Setting::where('key', 'kop_baris_1')->value('value') ?? 'GRIYA QUR\'AN BAITUL MANSHURIN',
            'baris_2' => Setting::where('key', 'kop_baris_2')->value('value') ?? 'Sistem Pendataan Peserta Didik Baru (SPSB)',
            'baris_3' => Setting::where('key', 'kop_baris_3')->value('value') ?? 'Jl. Contoh No. 123, Kota ABC, Propinsi XYZ | Telp: 0812-3456-7890',
        ];

        return view('pendaftaran_pdf', compact('santri', 'kop'));
    }

    public function bukti($id) {
        $santri = CalonSantri::with(['periode', 'gelombang'])->findOrFail($id);
        $adminUrl = route('admin.show', $santri->id);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($adminUrl);

        return view('bukti_pendaftaran', compact('santri', 'adminUrl', 'qrUrl'));
    }

    public function viewPublicDocument($id, string $field) {
        $allowedFields = ['foto_pas_siswa', 'foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk', 'tanda_tangan'];
        abort_unless(in_array($field, $allowedFields, true), 404);

        $santri = CalonSantri::findOrFail($id);
        $path = $santri->{$field};
        abort_if(!$path || !Storage::disk('public')->exists($path), 404, 'Berkas tidak ditemukan di storage.');

        return response()->file(Storage::disk('public')->path($path));
    }
}

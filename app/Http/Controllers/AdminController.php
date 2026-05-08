<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\Periode;
use App\Models\Gelombang;
use App\Models\ActivityLog;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class AdminController extends Controller
{
    public function index(Request $request) {
        $query = CalonSantri::latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nama_lengkap', 'like', "%$search%")
                  ->orWhere('nama_ayah', 'like', "%$search%")
                  ->orWhere('no_wa_ayah', 'like', "%$search%");
        }

        $pendaftar = $query->paginate(20);
        
        $stats = [
            'total' => CalonSantri::count(),
            'pending' => CalonSantri::where('status_pendaftaran', 'Pending')->count(),
            'diterima' => CalonSantri::where('status_pendaftaran', 'Diterima')->count(),
        ];

        $ocr_engine = Setting::where('key', 'ocr_engine')->value('value') ?? 'local';
        $ocr_webhook_url = Setting::where('key', 'ocr_webhook_url')->value('value') ?? 'https://n8n.griyaquran.web.id/webhook/ocr-ktp';
        $n8n_email_webhook_url = Setting::where('key', 'n8n_email_webhook_url')->value('value') ?? '';
        $n8n_whatsapp_webhook_url = Setting::where('key', 'n8n_whatsapp_webhook_url')->value('value') ?? '';
        $group_ikhwan_url = Setting::where('key', 'group_ikhwan_url')->value('value') ?? '';
        $group_akhwat_url = Setting::where('key', 'group_akhwat_url')->value('value') ?? '';
        
        // Pengaturan Direct AI
        $ai_endpoint = Setting::where('key', 'ai_endpoint')->value('value') ?? 'https://api.openai.com/v1/chat/completions';
        $ai_api_key = Setting::where('key', 'ai_api_key')->value('value') ?? '';
        $ai_model = Setting::where('key', 'ai_model')->value('value') ?? 'gpt-4o';
        
        $app_locked = Setting::where('key', 'app_locked')->value('value') == '1';
        
        $kop_baris_1 = Setting::where('key', 'kop_baris_1')->value('value') ?? 'GRIYA QUR\'AN BAITUL MANSHURIN';
        $kop_baris_2 = Setting::where('key', 'kop_baris_2')->value('value') ?? 'Sistem Pendataan Peserta Didik Baru (SPSB)';
        $kop_baris_3 = Setting::where('key', 'kop_baris_3')->value('value') ?? 'Jl. Contoh No. 123, Kota ABC, Propinsi XYZ | Telp: 0812-3456-7890';

        $periodes = Periode::all();
        $gelombangs = Gelombang::withCount('calonSantris')->get();
        $logs = ActivityLog::latest()->take(100)->get();
        $notificationLogs = NotificationLog::with('calonSantri')->latest()->take(100)->get();
        $followUpPendaftar = CalonSantri::with('gelombang')->latest()->get()->filter(function ($santri) {
            $statuses = collect($santri->dokumen_status ?? []);
            return !$santri->followup_sudah_masuk_grup
                || !$santri->followup_sudah_dihubungi
                || $santri->status_pendaftaran === 'Pending'
                || $statuses->contains(fn ($status) => in_array($status, ['kosong', 'perlu_perbaikan', 'menunggu_review'], true));
        })->take(100);

        return view('admin.dashboard', compact('pendaftar', 'ocr_engine', 'ocr_webhook_url', 'n8n_email_webhook_url', 'n8n_whatsapp_webhook_url', 'group_ikhwan_url', 'group_akhwat_url', 'stats', 'ai_endpoint', 'ai_api_key', 'ai_model', 'app_locked', 'periodes', 'gelombangs', 'logs', 'notificationLogs', 'followUpPendaftar', 'kop_baris_1', 'kop_baris_2', 'kop_baris_3'));
    }

    public function updateSettings(Request $request) {
        Setting::updateOrCreate(['key' => 'ocr_engine'], ['value' => $request->ocr_engine]);
        Setting::updateOrCreate(['key' => 'ocr_webhook_url'], ['value' => $request->ocr_webhook_url]);
        Setting::updateOrCreate(['key' => 'n8n_email_webhook_url'], ['value' => $request->n8n_email_webhook_url]);
        Setting::updateOrCreate(['key' => 'n8n_whatsapp_webhook_url'], ['value' => $request->n8n_whatsapp_webhook_url]);
        Setting::updateOrCreate(['key' => 'group_ikhwan_url'], ['value' => $request->group_ikhwan_url]);
        Setting::updateOrCreate(['key' => 'group_akhwat_url'], ['value' => $request->group_akhwat_url]);
        
        Setting::updateOrCreate(['key' => 'ai_endpoint'], ['value' => $request->ai_endpoint]);
        Setting::updateOrCreate(['key' => 'ai_api_key'], ['value' => $request->ai_api_key]);
        Setting::updateOrCreate(['key' => 'ai_model'], ['value' => $request->ai_model]);
        
        $app_locked = $request->has('app_locked') ? '1' : '0';
        Setting::updateOrCreate(['key' => 'app_locked'], ['value' => $app_locked]);

        Setting::updateOrCreate(['key' => 'kop_baris_1'], ['value' => $request->kop_baris_1]);
        Setting::updateOrCreate(['key' => 'kop_baris_2'], ['value' => $request->kop_baris_2]);
        Setting::updateOrCreate(['key' => 'kop_baris_3'], ['value' => $request->kop_baris_3]);
        
        return back()->with('success', 'Pengaturan berhasil disimpan!');
    }

    public function updateAccount(Request $request) {
        $user = auth()->user();
        
        $request->validate([
            'username' => 'required|string|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:6',
        ]);

        $user->username = $request->username;
        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }
        $user->save();

        return back()->with('success', 'Akun admin berhasil diperbarui!');
    }

    public function testAiConnection(Request $request) {
        try {
            $endpoint = $request->endpoint;
            $apiKey = $request->api_key;
            
            // Bentuk URL endpoint daftar model
            // Jika URL mengandung /chat/completions → ganti dengan /models
            // Jika tidak (seperti BytePlus: .../v3) → tambahkan /models di akhir
            if (str_contains($endpoint, '/chat/completions')) {
                $modelsEndpoint = str_replace('/chat/completions', '/models', $endpoint);
            } else {
                $modelsEndpoint = rtrim($endpoint, '/') . '/models';
            }
            
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->timeout(15)
                ->get($modelsEndpoint);
            
            if ($response->successful()) {
                $json = $response->json();
                // Cari daftar model: bisa di dalam 'data' (OpenAI) atau langsung di root (beberapa provider lain)
                $data = $json['data'] ?? $json;
                
                if (is_array($data)) {
                    $models = collect($data)->map(function($item) {
                        return is_array($item) ? ($item['id'] ?? ($item['model'] ?? null)) : $item;
                    })->filter()->values()->toArray();

                    if (!empty($models)) {
                        return response()->json(['success' => true, 'models' => $models]);
                    }
                }
            }
            
            // 2. Jika gagal tapi endpoint BytePlus, beri saran model BytePlus
            if (str_contains($endpoint, 'bytepluses') || str_contains($endpoint, 'volces')) {
                return response()->json([
                    'success' => true, 
                    'models' => ['doubao-pro-4k', 'doubao-pro-32k', 'doubao-pro-128k', 'doubao-lite-4k'],
                    'note' => 'Model dimuat dari saran BytePlus (Daftar otomatis tidak tersedia).'
                ]);
            }

            return response()->json([
                'success' => true, 
                'models' => ['gpt-4o', 'gpt-4o-mini', 'gemini-1.5-pro'],
                'note' => 'Model dimuat dari fallback standar.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Koneksi Gagal: ' . $e->getMessage()]);
        }
    }

    public function show($id) {
        $santri = CalonSantri::with('notificationLogs')->findOrFail($id);
        return view('admin.show', compact('santri'));
    }

    public function edit($id) {
        $santri = CalonSantri::findOrFail($id);
        return view('admin.edit', compact('santri'));
    }

    public function update(Request $request, $id) {
        $santri = CalonSantri::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'nullable|string|max:255',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'nik' => 'nullable|string|size:16',
            'nik_ayah' => 'nullable|string|size:16',
            'nik_ibu' => 'nullable|string|size:16',
            'email_ayah' => 'nullable|email',
            'email_ibu' => 'nullable|email',
            'status_tahsin_ayah' => 'nullable|in:Belum,Sudah',
            'status_tahsin_ibu' => 'nullable|in:Belum,Sudah',
        ]);

        $data = $request->only((new CalonSantri())->getFillable());
        $santri->update($data);

        ActivityLog::create([
            'aktivitas' => "Edit Data Peserta Didik: {$santri->nama_lengkap}",
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Data diperbarui!');
    }

    public function updateStatus(Request $request, $id) {
        $request->validate([
            'status_pendaftaran' => 'required|in:Pending,Diterima,Ditolak',
        ]);

        $santri = CalonSantri::findOrFail($id);
        $oldStatus = $santri->status_pendaftaran;
        $santri->update(['status_pendaftaran' => $request->status_pendaftaran]);
        
        ActivityLog::create([
            'aktivitas' => "Verifikasi Status: {$santri->nama_lengkap} -> {$request->status_pendaftaran}",
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);

        if ($oldStatus !== $request->status_pendaftaran) {
            $this->sendStatusVerificationNotifications($santri->fresh(), $oldStatus, $request->status_pendaftaran, $request);
        }

        return back()->with('success', 'Status berhasil diperbarui!');
    }

    private function sendStatusVerificationNotifications(CalonSantri $santri, string $oldStatus, string $newStatus, Request $request): void
    {
        $payload = $this->buildStatusVerificationPayload($santri, $oldStatus, $newStatus);

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
                    $response->successful() ? 'Webhook status verifikasi terkirim.' : $response->body()
                );
            } catch (\Throwable $e) {
                Log::warning('Webhook status verifikasi gagal.', [
                    'calon_santri_id' => $santri->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
                $this->recordNotificationLog($santri, $channel, $payload, 'failed', null, $e->getMessage());
            }
        }
    }

    private function buildStatusVerificationPayload(CalonSantri $santri, string $oldStatus, string $newStatus): array
    {
        $statusLabels = [
            'Pending' => 'Menunggu Verifikasi Data',
            'Diterima' => 'Data Lengkap / Terverifikasi',
            'Ditolak' => 'Data Tidak Valid / Tidak Dilanjutkan',
        ];

        $statusMessages = [
            'Pending' => 'Data ananda sedang dalam proses verifikasi panitia.',
            'Diterima' => 'Alhamdulillah, data ananda sudah dinyatakan lengkap dan terverifikasi oleh panitia.',
            'Ditolak' => 'Data ananda belum dapat dilanjutkan. Silakan cek catatan panitia atau hubungi admin untuk arahan berikutnya.',
        ];

        $emailRecipients = collect([
            ['role' => 'ayah', 'name' => $santri->nama_ayah, 'email' => $santri->email_ayah],
            ['role' => 'ibu', 'name' => $santri->nama_ibu, 'email' => $santri->email_ibu],
            ['role' => 'wali', 'name' => $santri->nama_wali, 'email' => $santri->email_wali],
        ])->filter(fn ($recipient) => !empty($recipient['email']))->unique('email')->values()->all();

        $whatsappRecipients = collect([
            ['role' => 'ayah', 'name' => $santri->nama_ayah, 'phone' => $santri->no_wa_ayah],
            ['role' => 'ibu', 'name' => $santri->nama_ibu, 'phone' => $santri->no_wa_ibu],
            ['role' => 'wali', 'name' => $santri->nama_wali, 'phone' => $santri->no_wa_wali],
        ])->filter(fn ($recipient) => !empty($recipient['phone']))->values()->all();

        $isIkhwan = $santri->jenis_kelamin === 'Laki-laki';
        $documentStatuses = collect($santri->dokumen_status ?? []);
        $needsRevision = $newStatus === 'Ditolak'
            || $documentStatuses->contains(fn ($status) => in_array($status, ['perlu_perbaikan', 'kosong'], true));

        return [
            'event' => 'status_verifikasi_diperbarui',
            'event_label' => 'Status Verifikasi Data Diperbarui',
            'santri' => [
                'id' => $santri->id,
                'nomor_pendaftaran' => $santri->nomor_pendaftaran,
                'nomor_pendataan' => $santri->nomor_pendaftaran,
                'nama_lengkap' => $santri->nama_lengkap,
                'nik' => $santri->nik,
                'jenis_kelamin' => $santri->jenis_kelamin,
                'status_pendaftaran' => $newStatus,
                'status_verifikasi' => $newStatus,
                'status_label' => $statusLabels[$newStatus] ?? $newStatus,
                'status_sebelumnya' => $oldStatus,
                'status_sebelumnya_label' => $statusLabels[$oldStatus] ?? $oldStatus,
                'pesan_status' => $statusMessages[$newStatus] ?? 'Status verifikasi data ananda telah diperbarui oleh panitia.',
                'waktu_update_status' => now()->format('Y-m-d H:i:s'),
                'perlu_revisi' => $needsRevision,
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
                'wali' => [
                    'nama' => $santri->nama_wali,
                    'no_wa' => $santri->no_wa_wali,
                    'email' => $santri->email_wali,
                ],
            ],
            'ringkasan' => [
                'judul' => 'Update Status Verifikasi Data SPSB',
                'baris' => [
                    'Nomor Pendataan' => $santri->nomor_pendaftaran,
                    'Nama Peserta Didik' => $santri->nama_lengkap,
                    'NIK' => $santri->nik,
                    'Status Sebelumnya' => $statusLabels[$oldStatus] ?? $oldStatus,
                    'Status Terbaru' => $statusLabels[$newStatus] ?? $newStatus,
                    'Waktu Update' => now()->format('d-m-Y H:i:s'),
                ],
            ],
            'dokumen' => [
                'status' => $santri->dokumen_status ?? [],
                'catatan' => $santri->dokumen_catatan,
                'perlu_revisi' => $needsRevision,
            ],
            'group' => [
                'kelas' => $isIkhwan ? 'ikhwan' : 'akhwat',
                'kategori_kelamin' => $isIkhwan ? 'putra' : 'putri',
                'link' => Setting::where('key', $isIkhwan ? 'group_ikhwan_url' : 'group_akhwat_url')->value('value') ?? '',
                'penerima_whatsapp' => $isIkhwan ? ['ayah', 'ibu'] : ['ibu'],
                'pesan_akses_ayah' => $isIkhwan
                    ? 'Nomor WhatsApp bapak dan ibu diperbolehkan untuk masuk grup kelas ikhwan ini.'
                    : 'Grup kelas sudah dikirim ke nomor Ibu. Yang diperbolehkan masuk grup kelas akhwat adalah nomor ibu.',
                'pesan_akses_ibu' => $isIkhwan
                    ? 'Nomor WhatsApp bapak dan ibu diperbolehkan untuk masuk grup kelas ikhwan ini.'
                    : 'Hanya nomor WhatsApp ibu yang diperbolehkan untuk masuk grup kelas akhwat ini.',
            ],
            'recipients' => [
                'email' => $emailRecipients,
                'whatsapp' => $whatsappRecipients,
            ],
            'links' => [
                'cek_status' => route('pendaftaran.cek_status'),
                'cetak' => route('pendaftaran.cetak', $santri->id),
                'bukti' => route('pendaftaran.bukti', $santri->id),
                'admin_detail' => route('admin.show', $santri->id),
                'revisi' => $santri->revisi_token ? route('pendaftaran.revisi', $santri->revisi_token) : null,
            ],
        ];
    }

    private function sendDocumentRevisionNotifications(CalonSantri $santri, Request $request): void
    {
        $payload = $this->buildDocumentRevisionPayload($santri);

        $webhooks = [
            'email' => Setting::where('key', 'n8n_email_webhook_url')->value('value'),
            'whatsapp' => Setting::where('key', 'n8n_whatsapp_webhook_url')->value('value'),
        ];

        foreach ($webhooks as $channel => $webhookUrl) {
            if (empty($payload['recipients'][$channel])) {
                $this->recordNotificationLog($santri, $channel, $payload, 'skipped', null, 'Tidak ada penerima untuk channel ini.');
                continue;
            }

            if (empty($webhookUrl)) {
                $this->recordNotificationLog($santri, $channel, $payload, 'skipped', null, 'Webhook n8n belum diisi.');
                continue;
            }

            try {
                $response = Http::timeout(15)->post($webhookUrl, $payload);
                $this->recordNotificationLog(
                    $santri,
                    $channel,
                    $payload,
                    $response->successful() ? 'success' : 'failed',
                    $response->status(),
                    $response->successful() ? 'Webhook revisi dokumen terkirim.' : $response->body()
                );
            } catch (\Throwable $e) {
                Log::warning('Webhook revisi dokumen gagal.', [
                    'calon_santri_id' => $santri->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
                $this->recordNotificationLog($santri, $channel, $payload, 'failed', null, $e->getMessage());
            }
        }
    }

    private function buildDocumentRevisionPayload(CalonSantri $santri): array
    {
        $emailRecipients = collect([
            ['role' => 'ayah', 'name' => $santri->nama_ayah, 'email' => $santri->email_ayah],
            ['role' => 'ibu', 'name' => $santri->nama_ibu, 'email' => $santri->email_ibu],
            ['role' => 'wali', 'name' => $santri->nama_wali, 'email' => $santri->email_wali],
        ])->filter(fn ($recipient) => !empty($recipient['email']))->unique('email')->values()->all();

        $whatsappRecipients = collect([
            ['role' => 'ayah', 'name' => $santri->nama_ayah, 'phone' => $santri->no_wa_ayah],
            ['role' => 'ibu', 'name' => $santri->nama_ibu, 'phone' => $santri->no_wa_ibu],
            ['role' => 'wali', 'name' => $santri->nama_wali, 'phone' => $santri->no_wa_wali],
        ])->filter(fn ($recipient) => !empty($recipient['phone']))->values()->all();

        return [
            'event' => 'revisi_dokumen_diminta',
            'event_label' => 'Revisi Data/Berkas Diminta',
            'santri' => [
                'id' => $santri->id,
                'nomor_pendaftaran' => $santri->nomor_pendaftaran,
                'nomor_pendataan' => $santri->nomor_pendaftaran,
                'nama_lengkap' => $santri->nama_lengkap,
                'nik' => $santri->nik,
                'jenis_kelamin' => $santri->jenis_kelamin,
                'status_pendaftaran' => $santri->status_pendaftaran,
                'status_verifikasi' => $santri->status_pendaftaran,
                'status_label' => 'Perlu Revisi Data/Berkas',
                'pesan_status' => 'Ada data atau berkas yang perlu diperbaiki. Silakan buka link revisi dan unggah ulang hanya dokumen yang diminta oleh panitia.',
                'perlu_revisi' => true,
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
                'wali' => [
                    'nama' => $santri->nama_wali,
                    'no_wa' => $santri->no_wa_wali,
                    'email' => $santri->email_wali,
                ],
            ],
            'ringkasan' => [
                'judul' => 'Revisi Data/Berkas SPSB',
                'baris' => [
                    'Nomor Pendataan' => $santri->nomor_pendaftaran,
                    'Nama Peserta Didik' => $santri->nama_lengkap,
                    'NIK' => $santri->nik,
                    'Status' => 'Perlu Revisi Data/Berkas',
                    'Waktu Permintaan Revisi' => now()->format('d-m-Y H:i:s'),
                ],
            ],
            'dokumen' => [
                'status' => $santri->dokumen_status ?? [],
                'catatan' => $santri->dokumen_catatan,
                'perlu_revisi' => true,
            ],
            'recipients' => [
                'email' => $emailRecipients,
                'whatsapp' => $whatsappRecipients,
            ],
            'links' => [
                'cek_status' => route('pendaftaran.cek_status'),
                'cetak' => route('pendaftaran.cetak', $santri->id),
                'bukti' => route('pendaftaran.bukti', $santri->id),
                'admin_detail' => route('admin.show', $santri->id),
                'revisi' => $santri->revisi_token ? route('pendaftaran.revisi', $santri->revisi_token) : null,
            ],
        ];
    }

    private function recordNotificationLog(CalonSantri $santri, string $channel, array $payload, string $status, ?int $httpStatus, ?string $message): void
    {
        if (!Schema::hasTable('notification_logs')) {
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

    public function updateDocumentVerification(Request $request, $id) {
        $santri = CalonSantri::findOrFail($id);

        $request->validate([
            'dokumen_status' => 'required|array',
            'dokumen_status.*' => 'required|in:menunggu_review,valid,perlu_perbaikan,kosong',
            'dokumen_catatan' => 'nullable|string|max:2000',
        ]);

        $needsRevision = collect($request->dokumen_status)
            ->contains(fn ($status) => in_array($status, ['perlu_perbaikan', 'kosong'], true));

        $santri->update([
            'dokumen_status' => $request->dokumen_status,
            'dokumen_catatan' => $request->dokumen_catatan,
            'revisi_diminta_pada' => $needsRevision ? now() : $santri->revisi_diminta_pada,
        ]);

        ActivityLog::create([
            'aktivitas' => "Verifikasi Dokumen: {$santri->nama_lengkap}",
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);

        if ($needsRevision) {
            $this->sendDocumentRevisionNotifications($santri->fresh(), $request);
        }

        return back()->with('success', 'Status dokumen berhasil diperbarui!');
    }

    public function viewUploadedDocument($id, string $field) {
        $allowedFields = ['foto_pas_siswa', 'foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk', 'tanda_tangan'];
        abort_unless(in_array($field, $allowedFields, true), 404);

        $santri = CalonSantri::findOrFail($id);
        $path = $santri->{$field};
        abort_if(!$path || !Storage::disk('public')->exists($path), 404, 'Berkas tidak ditemukan di storage.');

        return response()->file(Storage::disk('public')->path($path));
    }

    public function storePeriode(Request $request) {
        Periode::create(['nama_periode' => $request->nama_periode, 'is_active' => $request->has('is_active')]);
        return back()->with('success', 'Periode ditambahkan!');
    }

    public function updatePeriode(Request $request, $id) {
        if ($request->has('set_active')) {
            Periode::query()->update(['is_active' => false]);
            Periode::find($id)->update(['is_active' => true]);
            return back()->with('success', 'Periode aktif diubah!');
        }
        
        Periode::find($id)->update(['nama_periode' => $request->nama_periode, 'is_active' => $request->has('is_active')]);
        return back()->with('success', 'Periode diperbarui!');
    }

    public function deletePeriode($id) {
        Periode::destroy($id);
        return back()->with('success', 'Periode dihapus!');
    }

    public function storeGelombang(Request $request) {
        $request->validate([
            'nama_gelombang' => 'required|string|max:255',
            'kuota' => 'nullable|integer|min:1',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        if ($request->has('is_active')) {
            Gelombang::query()->update(['is_active' => false]);
        }

        Gelombang::create([
            'nama_gelombang' => $request->nama_gelombang,
            'kuota' => $request->kuota,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Gelombang ditambahkan!');
    }

    public function updateGelombang(Request $request, $id) {
        $gelombang = Gelombang::findOrFail($id);

        if ($request->has('set_active')) {
            Gelombang::query()->update(['is_active' => false]);
            $gelombang->update(['is_active' => true]);
            return back()->with('success', 'Gelombang aktif diubah!');
        }

        $gelombang->update([
            'nama_gelombang' => $request->nama_gelombang,
            'kuota' => $request->kuota,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
        ]);

        return back()->with('success', 'Gelombang diperbarui!');
    }

    public function deleteGelombang($id) {
        Gelombang::destroy($id);
        return back()->with('success', 'Gelombang dihapus!');
    }

    public function updateFollowUp(Request $request, $id) {
        $santri = CalonSantri::findOrFail($id);
        $santri->update([
            'followup_sudah_masuk_grup' => $request->has('followup_sudah_masuk_grup'),
            'followup_sudah_dihubungi' => $request->has('followup_sudah_dihubungi'),
            'followup_catatan' => $request->followup_catatan,
        ]);

        ActivityLog::create([
            'aktivitas' => "Update Follow-up: {$santri->nama_lengkap}",
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'Follow-up peserta didik diperbarui!');
    }
    
    public function exportData(Request $request) {
        $format = $request->format;
        $query = CalonSantri::query()->orderBy('id');

        if ($request->filled('status_pendaftaran')) {
            $query->where('status_pendaftaran', $request->status_pendaftaran);
        }

        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        if ($request->filled('gelombang_id')) {
            $query->where('gelombang_id', $request->gelombang_id);
        }

        $data = $query->get();
        
        ActivityLog::create([
            'aktivitas' => 'Export Data Peserta Didik ('.$format.') - '.$data->count().' data',
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);
        
        if ($format == 'csv') {
            $fileName = 'Data_Peserta_Didik_SPSB.csv';
            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];
            
            $columns = ['ID', 'Nama Anak', 'NIK Anak', 'Nama Ayah', 'Kontak Ayah', 'Status'];
            
            $callback = function() use($data, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id, $row->nama_lengkap, $row->nik, $row->nama_ayah, $row->no_wa_ayah, $row->status_pendaftaran
                    ]);
                }
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        }

        if ($format == 'excel') {
            return $this->downloadExcel($data);
        }

        if ($format == 'uploads') {
            return $this->downloadUploadedFiles($data);
        }
        
        // Return HTML for printing (PDF)
        return view('admin.print_all', compact('data'));
    }

    private function downloadExcel($data)
    {
        $columns = array_merge(
            ['id', 'created_at', 'updated_at'],
            (new CalonSantri())->getFillable()
        );

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Data_Peserta_Didik_SPSB_Full.xls"',
            'Cache-Control' => 'max-age=0',
        ];

        $callback = function () use ($data, $columns) {
            echo "\xEF\xBB\xBF";
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
            echo 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
            echo 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
            echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Worksheet ss:Name="Data Peserta Didik"><Table>';

            echo '<Row>';
            foreach ($columns as $column) {
                echo '<Cell><Data ss:Type="String">' . e($column) . '</Data></Cell>';
            }
            echo '</Row>';

            foreach ($data as $row) {
                echo '<Row>';
                foreach ($columns as $column) {
                    $value = $row->{$column} ?? '';

                    if (is_bool($value)) {
                        $value = $value ? 'Ya' : 'Tidak';
                    } elseif (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } elseif ($value instanceof \Carbon\CarbonInterface) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    echo '<Cell><Data ss:Type="String">' . e((string) $value) . '</Data></Cell>';
                }
                echo '</Row>';
            }

            echo '</Table></Worksheet></Workbook>';
        };

        return response()->stream($callback, 200, $headers);
    }

    private function downloadUploadedFiles($data)
    {
        if (!class_exists(ZipArchive::class)) {
            abort(500, 'Ekstensi ZIP belum tersedia di server.');
        }

        $tempDirectory = storage_path('app/temp');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . 'berkas-pendataan-' . now()->format('YmdHis') . '-' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $filesAdded = 0;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Gagal menyiapkan arsip berkas upload.');
        }

        $documentFields = [
            'foto_akta_anak' => 'akta',
            'foto_kk' => 'kk',
            'foto_ktp_ayah' => 'ktp-ayah',
            'foto_ktp_ibu' => 'ktp-ibu',
            'foto_pas_siswa' => 'foto-anak',
        ];

        foreach ($data as $santri) {
            $folderName = sprintf(
                '%03d-%s',
                $santri->id,
                $this->sanitizeFileName($santri->nama_lengkap ?: 'tanpa-nama')
            );

            foreach ($documentFields as $field => $label) {
                $storedPath = $santri->{$field};
                if (!$storedPath || !Storage::disk('public')->exists($storedPath)) {
                    continue;
                }

                $extension = pathinfo($storedPath, PATHINFO_EXTENSION) ?: 'bin';
                $zipEntry = $folderName . '/' . $label . '.' . $extension;
                if ($zip->addFile(Storage::disk('public')->path($storedPath), $zipEntry)) {
                    $filesAdded++;
                }
            }
        }

        if ($filesAdded === 0) {
            $zip->addFromString('README.txt', 'Belum ada berkas upload yang tersedia untuk diunduh.');
        }

        $zip->close();

        if (!is_file($zipPath)) {
            abort(500, 'Arsip berkas upload gagal dibuat.');
        }

        return response()->download(
            $zipPath,
            'Berkas_Pendataan_SPSB.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    private function sanitizeFileName(string $value): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9\-]+/', '-', $value);
        $sanitized = trim((string) $sanitized, '-');

        return $sanitized !== '' ? strtolower($sanitized) : 'tanpa-nama';
    }

    public function askAi(Request $request) {
        $request->validate([
            'question' => 'required|string|max:1000',
        ]);

        $question = $request->question;
        
        $endpoint = Setting::where('key', 'ai_endpoint')->value('value') ?? 'https://api.openai.com/v1/chat/completions';
        $apiKey = Setting::where('key', 'ai_api_key')->value('value');
        $model = Setting::where('key', 'ai_model')->value('value') ?? 'gpt-4o';

        // Pastikan endpoint mengarah ke /chat/completions
        if (!str_contains($endpoint, '/chat/completions')) {
            $endpoint = rtrim($endpoint, '/') . '/chat/completions';
        }

        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'API Key belum diatur di Pengaturan.']);
        }

        $stats = [
            'total' => CalonSantri::count(),
            'pending' => CalonSantri::where('status_pendaftaran', 'Pending')->count(),
            'diterima' => CalonSantri::where('status_pendaftaran', 'Diterima')->count(),
            'ditolak' => CalonSantri::where('status_pendaftaran', 'Ditolak')->count(),
            'putra' => CalonSantri::where('jenis_kelamin', 'Laki-laki')->count(),
            'putri' => CalonSantri::where('jenis_kelamin', 'Perempuan')->count(),
        ];

        $pendaftar = CalonSantri::select('nomor_pendaftaran', 'nama_lengkap', 'jenis_kelamin', 'nama_ayah', 'nama_ibu', 'no_wa_ayah', 'status_pendaftaran', 'created_at')
            ->latest()
            ->take(80)
            ->get();
        
        $context = "Kamu adalah Asisten AI untuk Administrator SPSB. Aplikasi ini dipakai untuk pendataan peserta didik baru yang sudah diterima. Jawab singkat, profesional, dan berdasarkan data yang tersedia.\n";
        $context .= "Statistik ringkas: " . json_encode($stats, JSON_UNESCAPED_UNICODE) . "\n";
        $context .= "Data peserta didik terbaru maksimal 80 baris: " . $pendaftar->toJson(JSON_UNESCAPED_UNICODE) . "\n";
        $context .= "Jika pertanyaan membutuhkan data yang tidak ada di konteks, katakan bahwa data tidak tersedia di konteks chat.";

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(60)
                ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $context],
                    ['role' => 'user', 'content' => $question]
                ],
                'temperature' => 0.2,
                'max_tokens' => 800,
            ]);

            if ($response->successful()) {
                $answer = $response->json('choices.0.message.content')
                    ?? $response->json('choices.0.text')
                    ?? $response->json('output_text')
                    ?? $response->json('message.content');

                if (!$answer) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Endpoint AI merespons, tetapi format jawabannya tidak dikenali.',
                    ]);
                }

                return response()->json(['success' => true, 'answer' => $answer]);
            }

            $errorBody = $response->json('error.message') ?? $response->json('message') ?? $response->body();
            return response()->json([
                'success' => false,
                'error' => 'AI Error [' . $response->status() . ']: ' . substr((string) $errorBody, 0, 500),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Koneksi AI gagal: ' . $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\OcrScan;
use App\Support\RegistrationInput;
use function Illuminate\Support\defer;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\NotificationLog;
use App\Models\Periode;
use App\Models\Gelombang;
use App\Models\GroupJoinLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PendaftaranController extends Controller
{
    public function index() {
        $settings = Setting::where('key', 'app_locked')->pluck('value', 'key');
        $app_locked = ($settings['app_locked'] ?? '0') == '1';
        if ($app_locked) {
            return view('locked');
        }

        return view('pendaftaran');
    }

    public function store(Request $request) {
        if (Setting::where('key', 'app_locked')->value('value') === '1') {
            throw \Illuminate\Validation\ValidationException::withMessages(['pendaftaran' => 'Pendataan sedang ditutup. Silakan hubungi panitia.']);
        }
        $this->validatePendaftaran($request);
        $stagedScans = $this->resolveStagedScans($request);

        $existingColumns = Schema::getColumnListing('calon_santris');
        $hasColumn = fn (string $column): bool => in_array($column, $existingColumns, true);
        $data = array_intersect_key(
            $request->only(RegistrationInput::FIELDS),
            array_flip($existingColumns)
        );

        $activeGelombang = (Schema::hasTable('gelombangs') && $hasColumn('gelombang_id'))
            ? Gelombang::where('is_active', true)->first()
            : null;
        if ($activeGelombang && $activeGelombang->kuota && CalonSantri::where('gelombang_id', $activeGelombang->id)->count() >= $activeGelombang->kuota) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'gelombang' => "Kuota {$activeGelombang->nama_gelombang} sudah penuh. Silakan hubungi panitia.",
            ]);
        }


        $uploadedPaths = [];
        try {
            // Upload Dokumen ke Storage VPS
            $docs = ['foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk', 'foto_pas_siswa'];
            foreach ($docs as $doc) {
                if ($request->hasFile($doc)) {
                    $path = $request->file($doc)->store('pendaftaran', 'public');
                    if (!$path) throw new \RuntimeException('Lampiran gagal disimpan.');
                    $data[$doc] = $path;
                    $uploadedPaths[] = $path;
                } elseif (isset($stagedScans[$doc])) {
                    $scan = $stagedScans[$doc];
                    $sourcePath = Storage::disk('local')->path($scan->file_path);
                    if (!is_file($sourcePath)) throw new \RuntimeException('Lampiran sementara sudah tidak tersedia.');
                    $path = Storage::disk('public')->putFile('pendaftaran', new \Illuminate\Http\File($sourcePath));
                    if (!$path) throw new \RuntimeException('Lampiran gagal dipindahkan ke penyimpanan pendaftaran.');
                    $data[$doc] = $path;
                    $uploadedPaths[] = $path;
                }
            }

            // Tanda tangan base64
            if ($request->filled('tanda_tangan_base64')) {
                $image_parts = explode(";base64,", $request->tanda_tangan_base64);
                if(count($image_parts) == 2) {
                    $image_base64 = base64_decode($image_parts[1]);
                    $fileName = 'pendaftaran/ttd_' . uniqid() . '.png';
                    if (!Storage::disk('public')->put($fileName, $image_base64)) throw new \RuntimeException('Tanda tangan gagal disimpan.');
                    $uploadedPaths[] = $fileName;
                    $data['tanda_tangan'] = $fileName;
                }
            }

            // Hapus field non-database sebelum mass assignment
            unset($data['tanda_tangan_base64']);

            // Pastikan checkbox pernyataan diubah ke boolean (1/0) bukannya string 'on'
            $data['pernyataan_kebenaran_data'] = $request->has('pernyataan_kebenaran_data');
            $data['status_pendaftaran'] = 'Pending';
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

            $santri = DB::transaction(function () use ($data, $request, $hasColumn, $activeGelombang, $stagedScans) {
                foreach ($stagedScans as $scan) {
                    $lockedScan = OcrScan::whereKey($scan->id)->lockForUpdate()->first();
                    if (!$lockedScan || $lockedScan->used_at || $lockedScan->expires_at->isPast()
                        || !hash_equals($lockedScan->session_hash, hash('sha256', $request->session()->getId()))) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'staged_files.' . $this->registrationFieldForTarget($scan->target) => 'Lampiran sudah kedaluwarsa atau tidak dapat digunakan. Silakan pilih foto kembali.',
                        ]);
                    }
                    $lockedScan->forceFill(['used_at' => now()])->save();
                }

                if ($activeGelombang) {
                    $locked = Gelombang::whereKey($activeGelombang->id)->lockForUpdate()->first();
                    if ($locked && $locked->kuota && CalonSantri::where('gelombang_id', $locked->id)->count() >= $locked->kuota) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['gelombang' => 'Kuota gelombang sudah penuh. Silakan hubungi panitia.']);
                    }
                }
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

        } catch (\Throwable $error) {
            // Only files created by this request are removed when saving fails.
            Storage::disk('public')->delete($uploadedPaths);
            if ($error instanceof \Illuminate\Database\UniqueConstraintViolationException && str_contains($error->getMessage(), 'nik')) {
                throw \Illuminate\Validation\ValidationException::withMessages(['nik' => 'NIK anak sudah terdaftar. Silakan gunakan halaman Cek Status Data.']);
            }
            throw $error;
        }

        foreach ($stagedScans as $scan) {
            $disk = Storage::disk('local');
            if (!$disk->exists($scan->file_path) || $disk->delete($scan->file_path)) {
                $scan->delete();
            }
        }

        // Store the registration first; webhook latency must not delay the success page.
        defer(function () use ($santri, $request) {
            try {
                $this->sendPendaftaranNotifications($santri, $request);
            } catch (\Throwable $e) {
                Log::warning('Notifikasi pendataan gagal setelah data tersimpan.', [
                    'calon_santri_id' => $santri->id, 'error' => $e->getMessage(),
                ]);
            }
        });

        $success = redirect('/pendaftaran/sukses')
            ->with('nama_santri', $santri->nama_lengkap)
            ->with('santri_id', $santri->id)
            ->with('bukti_url', URL::signedRoute('pendaftaran.bukti', ['id' => $santri->id]))
            ->with('nomor_pendaftaran', $santri->nomor_pendaftaran ?? $this->generateNomorPendaftaran($santri));

        return $request->expectsJson()
            ? response()->json(['saved' => true, 'redirect_url' => route('pendaftaran.sukses')])
            : $success;
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
            ? 'Untuk peserta didik putra, Bapak dan Ibu dipersilakan bergabung ke grup kelas ikhwan.'
            : 'Untuk peserta didik putri, link grup kelas akhwat dikirim khusus ke nomor WhatsApp Ibu. Hanya Ibu yang diperbolehkan bergabung ke group tersebut.';

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

        $emailRecipients = $this->withGroupJoinLinks($santri, $emailRecipients, 'email', $kelas, $groupLink);
        $whatsappRecipients = $this->withGroupJoinLinks($santri, $whatsappRecipients, 'whatsapp', $kelas, $groupLink);

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
                'cetak' => URL::signedRoute('pendaftaran.cetak', ['id' => $santri->id]),
                'admin_detail' => route('admin.show', $santri->id),
                'cek_status' => route('pendaftaran.cek_status'),
                'revisi' => route('pendaftaran.revisi', $santri->revisi_token),
            ],
        ];
    }

    private function withGroupJoinLinks(CalonSantri $santri, array $recipients, string $channel, string $groupType, string $targetUrl): array
    {
        if (!$targetUrl) {
            return $recipients;
        }

        return collect($recipients)->map(function (array $recipient) use ($santri, $channel, $groupType, $targetUrl) {
            if (!$this->roleMayReceiveGroupLink($recipient['role'] ?? '', $groupType)) {
                return $recipient + ['group_join_url' => null];
            }

            if (!Schema::hasTable('group_join_links')) {
                return $recipient + ['group_join_url' => $targetUrl];
            }

            $recipient['group_join_url'] = $this->groupJoinTrackingUrl(
                $santri,
                (string) $recipient['role'],
                $channel,
                $groupType,
                $targetUrl
            );

            return $recipient;
        })->all();
    }

    private function roleMayReceiveGroupLink(string $role, string $groupType): bool
    {
        if ($groupType === 'akhwat') {
            return $role === 'ibu';
        }

        return in_array($role, ['ayah', 'ibu'], true);
    }

    private function groupJoinTrackingUrl(CalonSantri $santri, string $role, string $channel, string $groupType, string $targetUrl): string
    {
        $link = GroupJoinLink::where([
            'calon_santri_id' => $santri->id,
            'role' => $role,
            'channel' => $channel,
            'group_type' => $groupType,
        ])->first();

        if (!$link) {
            $link = GroupJoinLink::create([
                'calon_santri_id' => $santri->id,
                'token' => $this->uniqueGroupJoinToken(),
                'role' => $role,
                'channel' => $channel,
                'group_type' => $groupType,
                'target_url' => $targetUrl,
                'expires_at' => now()->addMonths(6),
            ]);
        } else {
            $link->update([
                'target_url' => $targetUrl,
                'expires_at' => $link->expires_at ?? now()->addMonths(6),
            ]);
        }

        return route('group.join', ['token' => $link->token]);
    }

    private function uniqueGroupJoinToken(): string
    {
        do {
            $token = Str::random(56);
        } while (GroupJoinLink::where('token', $token)->exists());

        return $token;
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
            'foto_akta_anak' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_kk' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
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
        RegistrationInput::normalize($request);
        $request->validate(array_merge(RegistrationInput::optionalRules(), [
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'nik' => 'required|digits:16|unique:calon_santris,nik',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date_format:Y-m-d|before:today',
            'agama' => 'required|string|max:100',
            'alamat_lengkap' => 'required|string',
            'rt_rw' => ['required', 'regex:/^\d{1,3}\/\d{1,3}$/'],
            'kelurahan_desa' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'propinsi' => 'required|string|max:255',
            'kode_pos' => 'required|digits:5',
            'jenis_tinggal' => 'required|string|max:255',
            'alat_transportasi' => 'required|string|max:255',
            'nama_ayah' => 'required|string|max:255',
            'nik_ayah' => 'required|digits:16|different:nik',
            'no_wa_ayah' => ['required', 'string', 'max:25', 'regex:/^(08|628)[0-9]{8,13}$/'],
            'nama_ibu' => 'required|string|max:255',
            'nik_ibu' => 'required|digits:16|different:nik,nik_ayah',
            'no_wa_ibu' => ['required', 'string', 'max:25', 'regex:/^(08|628)[0-9]{8,13}$/'],
            'penandatangan_nama' => 'required|string|max:255',
            'pernyataan_kebenaran_data' => 'accepted',
            'foto_akta_anak' => 'required_without:staged_files.foto_akta_anak|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_kk' => 'required_without:staged_files.foto_kk|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_ktp_ayah' => 'required_without:staged_files.foto_ktp_ayah|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_ktp_ibu' => 'required_without:staged_files.foto_ktp_ibu|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_pas_siswa' => 'required_without:staged_files.foto_pas_siswa|image|mimes:jpg,jpeg,png,webp|max:10240',
            'staged_files.foto_akta_anak' => 'required_without:foto_akta_anak|nullable|regex:/^[0-9a-f]{64}$/',
            'staged_files.foto_kk' => 'required_without:foto_kk|nullable|regex:/^[0-9a-f]{64}$/',
            'staged_files.foto_ktp_ayah' => 'required_without:foto_ktp_ayah|nullable|regex:/^[0-9a-f]{64}$/',
            'staged_files.foto_ktp_ibu' => 'required_without:foto_ktp_ibu|nullable|regex:/^[0-9a-f]{64}$/',
            'staged_files.foto_pas_siswa' => 'required_without:foto_pas_siswa|nullable|regex:/^[0-9a-f]{64}$/',
            'tanda_tangan_base64' => ['required', 'string', 'max:3000000', function ($attribute, $value, $fail) {
                if (!str_starts_with($value, 'data:image/png;base64,')) { $fail('Tanda tangan belum valid. Silakan tanda tangan ulang.'); return; }
                $decoded = base64_decode(substr($value, 22), true);
                $info = $decoded ? @getimagesizefromstring($decoded) : false;
                if (!$info || $info[2] !== IMAGETYPE_PNG) $fail('Tanda tangan belum valid. Silakan tanda tangan ulang.');
            }],
        ]), [
            'required' => ':attribute wajib diisi.', 'digits' => ':attribute harus terdiri dari :digits angka.',
            'nik.unique' => 'NIK anak sudah terdaftar. Silakan gunakan halaman Cek Status Data atau hubungi panitia.',
            'different' => ':attribute tidak boleh sama dengan NIK anggota keluarga lainnya.',
            'before' => ':attribute harus sebelum hari ini.', 'date_format' => 'Tanggal pada :attribute belum valid.',
            'email' => 'Alamat :attribute belum valid.', 'regex' => 'Format :attribute belum sesuai.',
            'image' => ':attribute harus berupa foto.', 'max' => ':attribute melebihi batas yang diizinkan.',
        ], [
            'nik' => 'NIK anak', 'nik_ayah' => 'NIK Bapak', 'nik_ibu' => 'NIK Ibu',
            'tanggal_lahir' => 'Tanggal lahir anak', 'nama_lengkap' => 'Nama anak',
            'no_wa_ayah' => 'Nomor WhatsApp Bapak', 'no_wa_ibu' => 'Nomor WhatsApp Ibu',
            'rt_rw' => 'RT/RW (contoh 008/002)', 'kode_pos' => 'Kode pos',
            'foto_akta_anak' => 'Akta Anak', 'foto_kk' => 'Kartu Keluarga',
            'foto_ktp_ayah' => 'KTP Bapak', 'foto_ktp_ibu' => 'KTP Ibu', 'foto_pas_siswa' => 'Foto anak',
            'tanda_tangan_base64' => 'Tanda tangan',
        ]);
    }

    /** @return array<string, OcrScan> */
    private function resolveStagedScans(Request $request): array
    {
        $targets = [
            'foto_akta_anak' => 'akta', 'foto_kk' => 'kk', 'foto_ktp_ayah' => 'ayah',
            'foto_ktp_ibu' => 'ibu', 'foto_pas_siswa' => 'foto',
        ];
        $resolved = [];
        foreach ($targets as $field => $target) {
            $token = $request->input('staged_files.' . $field);
            if (!$token) continue;
            $scan = OcrScan::where('token_hash', hash('sha256', (string) $token))
                ->where('session_hash', hash('sha256', $request->session()->getId()))
                ->where('target', $target)
                ->whereNull('used_at')
                ->first();
            if (!$scan || $scan->expires_at->isPast() || !is_file(Storage::disk('local')->path($scan->file_path))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'staged_files.' . $field => 'Lampiran sementara tidak valid atau sudah kedaluwarsa. Silakan pilih foto kembali.',
                ]);
            }
            $resolved[$field] = $scan;
        }

        return $resolved;
    }

    private function registrationFieldForTarget(string $target): string
    {
        return match ($target) {
            'akta' => 'foto_akta_anak', 'kk' => 'foto_kk', 'ayah' => 'foto_ktp_ayah',
            'ibu' => 'foto_ktp_ibu', default => 'foto_pas_siswa',
        };
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

    public function redirectGroupJoin(Request $request, string $token) {
        $link = GroupJoinLink::where('token', $token)->firstOrFail();

        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(410, 'Link grup sudah tidak aktif. Silakan hubungi panitia.');
        }

        $link->forceFill([
            'clicked_at' => now(),
            'click_count' => $link->click_count + 1,
            'last_clicked_ip' => $request->ip(),
            'last_clicked_user_agent' => Str::limit((string) $request->userAgent(), 1000),
        ])->save();

        if (Schema::hasTable('activity_logs')) {
            $roleLabel = $link->role === 'ibu' ? 'Ibu' : 'Ayah';
            ActivityLog::create([
                'aktivitas' => "Link grup {$link->group_type} dibuka oleh {$roleLabel}: {$link->calonSantri?->nama_lengkap}",
                'aktor' => 'Orang Tua / Wali',
                'ip_address' => $request->ip(),
            ]);
        }

        return redirect()->away($link->target_url);
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

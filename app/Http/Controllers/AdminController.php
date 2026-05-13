<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\Periode;
use App\Models\Gelombang;
use App\Models\ActivityLog;
use App\Models\NotificationLog;
use App\Models\GroupJoinLink;
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
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nama_ayah', 'like', "%{$search}%")
                    ->orWhere('no_wa_ayah', 'like', "%{$search}%");
            });
        }

        $pendaftar = $query->paginate(20);
        
        $stats = [
            'total' => CalonSantri::count(),
            'pending' => CalonSantri::where('status_pendaftaran', 'Pending')->count(),
            'diterima' => CalonSantri::where('status_pendaftaran', 'Diterima')->count(),
            'sampah' => CalonSantri::onlyTrashed()->count(),
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
        $followUpRelations = ['gelombang'];
        if (Schema::hasTable('group_join_links')) {
            $followUpRelations[] = 'groupJoinLinks';
        }

        $followUpPendaftar = CalonSantri::with($followUpRelations)->latest()->get()->filter(function ($santri) {
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
        $relations = ['notificationLogs'];
        if (Schema::hasTable('group_join_links')) {
            $relations[] = 'groupJoinLinks';
        }

        $santri = CalonSantri::with($relations)->findOrFail($id);
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

    public function trash(Request $request)
    {
        $query = CalonSantri::onlyTrashed()->latest('deleted_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nama_ayah', 'like', "%{$search}%")
                    ->orWhere('nama_ibu', 'like', "%{$search}%")
                    ->orWhere('nomor_pendaftaran', 'like', "%{$search}%");
            });
        }

        $trashedPendaftar = $query->paginate(20);

        return view('admin.trash', compact('trashedPendaftar'));
    }

    public function moveToTrash($id)
    {
        $santri = CalonSantri::findOrFail($id);
        $santri->delete();

        ActivityLog::create([
            'aktivitas' => "Pindah ke Sampah: {$santri->nama_lengkap}",
            'aktor' => 'Admin',
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Data peserta didik dipindahkan ke sampah.');
    }

    public function restoreFromTrash($id)
    {
        $santri = CalonSantri::onlyTrashed()->findOrFail($id);
        $santri->restore();

        ActivityLog::create([
            'aktivitas' => "Pulihkan dari Sampah: {$santri->nama_lengkap}",
            'aktor' => 'Admin',
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'Data peserta didik berhasil dipulihkan.');
    }

    public function forceDeleteFromTrash(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password admin tidak sesuai. Data tidak dihapus permanen.']);
        }

        $santri = CalonSantri::onlyTrashed()->findOrFail($id);
        $name = $santri->nama_lengkap;
        $this->deleteSantriUploadedFiles($santri);
        $santri->forceDelete();

        ActivityLog::create([
            'aktivitas' => "Hapus Permanen: {$name}",
            'aktor' => 'Admin',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Data peserta didik berhasil dihapus permanen.');
    }

    public function updateStatus(Request $request, $id) {
        $request->validate([
            'status_pendaftaran' => 'required|in:Pending,Diterima,Ditolak',
        ]);

        $santri = CalonSantri::findOrFail($id);
        $santri->update(['status_pendaftaran' => $request->status_pendaftaran]);
        
        ActivityLog::create([
            'aktivitas' => "Verifikasi Status: {$santri->nama_lengkap} -> {$request->status_pendaftaran}",
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'Status berhasil diperbarui!');
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

    private function deleteSantriUploadedFiles(CalonSantri $santri): void
    {
        $fields = ['foto_ktp_ayah', 'foto_ktp_ibu', 'foto_akta_anak', 'foto_kk', 'foto_pas_siswa', 'tanda_tangan'];

        foreach ($fields as $field) {
            $path = $santri->{$field};
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
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

        $chatContext = $this->buildAiChatContext($question);

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(60)
                ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $chatContext['system']],
                    ['role' => 'user', 'content' => $chatContext['user']]
                ],
                'temperature' => 0.2,
                'max_tokens' => 1400,
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

    private function buildAiChatContext(string $question): array
    {
        $stats = [
            'total' => CalonSantri::count(),
            'pending' => CalonSantri::where('status_pendaftaran', 'Pending')->count(),
            'diterima' => CalonSantri::where('status_pendaftaran', 'Diterima')->count(),
            'ditolak' => CalonSantri::where('status_pendaftaran', 'Ditolak')->count(),
            'putra' => CalonSantri::where('jenis_kelamin', 'Laki-laki')->count(),
            'putri' => CalonSantri::where('jenis_kelamin', 'Perempuan')->count(),
            'perlu_review_dokumen' => CalonSantri::query()
                ->get()
                ->filter(fn ($santri) => collect($santri->dokumen_status ?? [])->contains(fn ($status) => in_array($status, ['kosong', 'perlu_perbaikan', 'menunggu_review'], true)))
                ->count(),
            'link_grup_dibuat' => Schema::hasTable('group_join_links') ? GroupJoinLink::count() : 0,
            'link_grup_sudah_dibuka' => Schema::hasTable('group_join_links') ? GroupJoinLink::whereNotNull('clicked_at')->count() : 0,
            'link_grup_belum_dibuka' => Schema::hasTable('group_join_links') ? GroupJoinLink::whereNull('clicked_at')->count() : 0,
            'notifikasi_success' => Schema::hasTable('notification_logs') ? NotificationLog::where('status', 'success')->count() : 0,
            'notifikasi_failed' => Schema::hasTable('notification_logs') ? NotificationLog::where('status', 'failed')->count() : 0,
            'notifikasi_skipped' => Schema::hasTable('notification_logs') ? NotificationLog::where('status', 'skipped')->count() : 0,
        ];

        $relations = $this->aiContextRelations();
        $summaryRows = CalonSantri::with($relations)
            ->latest()
            ->take(120)
            ->get()
            ->map(fn (CalonSantri $santri) => $this->summarizeSantriForAi($santri))
            ->values();

        $matchedCandidates = $this->findAiRelevantSantri($question);
        $candidates = $matchedCandidates;
        if ($matchedCandidates->isEmpty()) {
            $candidates = CalonSantri::with($relations)->latest()->take(8)->get();
        }

        $detailedRows = $candidates
            ->take(12)
            ->map(fn (CalonSantri $santri) => $this->detailSantriForAi($santri))
            ->values();

        $operationalRows = $this->buildAiOperationalRows($question, $matchedCandidates);

        $attachments = $this->buildAiDocumentAttachments($matchedCandidates, $question);
        $questionText = "Pertanyaan admin: {$question}";
        if (count($attachments) > 0) {
            $questionText .= "\n\nBerkas gambar yang relevan sudah dilampirkan setelah teks ini. Baca isi dokumen dari gambar tersebut bila pertanyaan menyangkut isi/validitas berkas.";
        }

        $userContent = [
            ['type' => 'text', 'text' => $questionText],
        ];

        foreach ($attachments as $attachment) {
            $userContent[] = [
                'type' => 'text',
                'text' => "Lampiran: {$attachment['label']} milik {$attachment['owner']}.",
            ];
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $attachment['url'],
                ],
            ];
        }

        $system = "Kamu adalah Asisten AI untuk Administrator SPSB. Aplikasi ini dipakai untuk pendataan peserta didik baru yang sudah diterima.\n"
            . "Jawab dalam bahasa Indonesia, ringkas, profesional, dan hanya berdasarkan konteks yang tersedia.\n"
            . "Jika membaca dokumen gambar, jelaskan tingkat keyakinan dan sebutkan bila teks tidak jelas/terpotong. Jangan mengarang data yang tidak terlihat.\n"
            . "Untuk pertanyaan hitungan atau rekap, gunakan statistik, ringkasan data, dan konteks operasional. Untuk pertanyaan tentang satu peserta, gunakan data detail form, tracking link grup, log notifikasi, dan lampiran dokumen.\n"
            . "Status operasional penting: clicked_at pada link grup berarti link grup WhatsApp sudah dibuka; clicked_at kosong berarti belum dibuka. Status notifikasi success berarti webhook terkirim ke endpoint, failed berarti endpoint gagal/error, skipped berarti tidak dikirim karena penerima/webhook tidak tersedia.\n"
            . "Statistik ringkas: " . json_encode($stats, JSON_UNESCAPED_UNICODE) . "\n"
            . "Ringkasan peserta terbaru maksimal 120 baris: " . $summaryRows->toJson(JSON_UNESCAPED_UNICODE) . "\n"
            . "Konteks operasional link grup dan notifikasi: " . $operationalRows->toJson(JSON_UNESCAPED_UNICODE) . "\n"
            . "Detail form kandidat yang paling relevan: " . $detailedRows->toJson(JSON_UNESCAPED_UNICODE);

        return [
            'system' => $system,
            'user' => count($attachments) > 0 ? $userContent : $question,
        ];
    }

    private function findAiRelevantSantri(string $question)
    {
        $normalized = Str::lower($question);
        $tokens = collect(preg_split('/[^a-zA-Z0-9]+/u', $normalized))
            ->filter(fn ($token) => strlen($token) >= 3)
            ->unique()
            ->values();

        return CalonSantri::with($this->aiContextRelations())
            ->latest()
            ->get()
            ->map(function (CalonSantri $santri) use ($normalized, $tokens) {
                $haystack = Str::lower(collect([
                    $santri->nomor_pendaftaran,
                    $santri->nama_lengkap,
                    $santri->nik,
                    $santri->nisn,
                    $santri->nama_ayah,
                    $santri->nik_ayah,
                    $santri->no_wa_ayah,
                    $santri->nama_ibu,
                    $santri->nik_ibu,
                    $santri->no_wa_ibu,
                    $santri->nama_wali,
                    $santri->nik_wali,
                    $santri->no_wa_wali,
                ])->filter()->implode(' '));

                $score = 0;
                foreach ($tokens as $token) {
                    if (str_contains($haystack, $token)) {
                        $score++;
                    }
                }

                foreach ([$santri->nomor_pendaftaran, $santri->nik, $santri->nik_ayah, $santri->nik_ibu, $santri->no_wa_ayah, $santri->no_wa_ibu] as $exact) {
                    if ($exact && str_contains($normalized, Str::lower((string) $exact))) {
                        $score += 5;
                    }
                }

                return ['score' => $score, 'santri' => $santri];
            })
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->pluck('santri')
            ->take(12)
            ->values();
    }

    private function summarizeSantriForAi(CalonSantri $santri): array
    {
        return [
            'nomor' => $santri->nomor_pendaftaran,
            'nama' => $santri->nama_lengkap,
            'jk' => $santri->jenis_kelamin,
            'nik' => $santri->nik,
            'ttl' => trim(($santri->tempat_lahir ?? '') . ', ' . ($santri->tanggal_lahir ?? ''), ', '),
            'ayah' => $santri->nama_ayah,
            'ibu' => $santri->nama_ibu,
            'wa_utama' => $santri->no_wa_ayah ?: $santri->no_wa_ibu ?: $santri->no_wa_wali,
            'status' => $santri->status_pendaftaran,
            'periode' => $santri->periode?->nama_periode,
            'gelombang' => $santri->gelombang?->nama_gelombang,
            'dokumen_status' => $santri->dokumen_status,
            'followup' => [
                'sudah_masuk_grup' => (bool) $santri->followup_sudah_masuk_grup,
                'sudah_dihubungi' => (bool) $santri->followup_sudah_dihubungi,
                'catatan' => $santri->followup_catatan,
            ],
            'link_grup' => $this->summarizeGroupJoinLinksForAi($santri),
            'notifikasi_terakhir' => $this->summarizeNotificationLogsForAi($santri, 3),
            'dibuat' => optional($santri->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function detailSantriForAi(CalonSantri $santri): array
    {
        $labels = $this->aiFormFieldLabels();
        $data = ['id' => $santri->id];

        foreach ($labels as $field => $label) {
            $value = $santri->{$field};
            if ($value === null || $value === '' || in_array($field, array_keys($this->aiDocumentLabels()), true)) {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i');
            }

            if (is_bool($value)) {
                $value = $value ? 'Ya' : 'Tidak';
            }

            $data[$label] = $value;
        }

        $data['Periode'] = $santri->periode?->nama_periode;
        $data['Gelombang'] = $santri->gelombang?->nama_gelombang;
        $data['Follow-up operasional'] = [
            'sudah_masuk_grup' => (bool) $santri->followup_sudah_masuk_grup,
            'sudah_dihubungi' => (bool) $santri->followup_sudah_dihubungi,
            'catatan' => $santri->followup_catatan,
        ];
        $data['tracking_link_grup'] = $this->summarizeGroupJoinLinksForAi($santri, true);
        $data['log_notifikasi'] = $this->summarizeNotificationLogsForAi($santri, 8);
        $data['dokumen_terunggah'] = collect($this->aiDocumentLabels())
            ->mapWithKeys(fn ($label, $field) => [$label => [
                'ada' => (bool) $santri->{$field},
                'status' => $santri->dokumen_status[$field] ?? null,
            ]])
            ->all();

        return $data;
    }

    private function aiContextRelations(): array
    {
        $relations = ['periode', 'gelombang'];

        if (Schema::hasTable('group_join_links')) {
            $relations[] = 'groupJoinLinks';
        }

        if (Schema::hasTable('notification_logs')) {
            $relations[] = 'notificationLogs';
        }

        return $relations;
    }

    private function buildAiOperationalRows(string $question, $matchedCandidates)
    {
        $question = Str::lower($question);
        $isOperationalQuestion = Str::contains($question, [
            'link group', 'link grup', 'grup whatsapp', 'group whatsapp', 'buka link',
            'dibuka', 'belum buka', 'belum dibuka', 'masuk grup', 'terkirim',
            'terkirim atau tidak', 'gagal', 'failed', 'success', 'skipped',
            'notifikasi', 'whatsapp', 'wa ', 'email', 'webhook', 'dihubungi',
        ]);

        if (!$isOperationalQuestion) {
            return collect();
        }

        $rows = $matchedCandidates->isNotEmpty()
            ? $matchedCandidates
            : CalonSantri::with($this->aiContextRelations())->latest()->take(80)->get();

        return $rows
            ->map(fn (CalonSantri $santri) => [
                'nomor' => $santri->nomor_pendaftaran,
                'nama' => $santri->nama_lengkap,
                'ayah' => $santri->nama_ayah,
                'ibu' => $santri->nama_ibu,
                'wa_ayah' => $santri->no_wa_ayah,
                'wa_ibu' => $santri->no_wa_ibu,
                'email_ayah' => $santri->email_ayah,
                'email_ibu' => $santri->email_ibu,
                'followup_sudah_masuk_grup' => (bool) $santri->followup_sudah_masuk_grup,
                'followup_sudah_dihubungi' => (bool) $santri->followup_sudah_dihubungi,
                'followup_catatan' => $santri->followup_catatan,
                'link_grup' => $this->summarizeGroupJoinLinksForAi($santri, true),
                'notifikasi' => $this->summarizeNotificationLogsForAi($santri, 6),
            ])
            ->values();
    }

    private function summarizeGroupJoinLinksForAi(CalonSantri $santri, bool $includeDetails = false): array
    {
        $links = $santri->relationLoaded('groupJoinLinks') ? $santri->groupJoinLinks : collect();

        if ($links->isEmpty()) {
            return [
                'total' => 0,
                'sudah_dibuka' => 0,
                'belum_dibuka' => 0,
                'detail' => [],
            ];
        }

        $details = $links
            ->sortBy(fn ($link) => $link->role . '-' . $link->channel . '-' . $link->group_type)
            ->map(fn ($link) => [
                'role' => $link->role,
                'channel' => $link->channel,
                'group_type' => $link->group_type,
                'status_buka' => $link->clicked_at ? 'sudah_dibuka' : 'belum_dibuka',
                'clicked_at' => optional($link->clicked_at)->format('Y-m-d H:i'),
                'click_count' => (int) $link->click_count,
                'terakhir_dibuka_ip' => $includeDetails ? $link->last_clicked_ip : null,
                'dibuat' => optional($link->created_at)->format('Y-m-d H:i'),
            ])
            ->values()
            ->all();

        return [
            'total' => $links->count(),
            'sudah_dibuka' => $links->whereNotNull('clicked_at')->count(),
            'belum_dibuka' => $links->whereNull('clicked_at')->count(),
            'detail' => $includeDetails ? $details : collect($details)->take(4)->values()->all(),
        ];
    }

    private function summarizeNotificationLogsForAi(CalonSantri $santri, int $limit = 5): array
    {
        $logs = $santri->relationLoaded('notificationLogs') ? $santri->notificationLogs : collect();

        return $logs
            ->sortByDesc('created_at')
            ->take($limit)
            ->map(fn ($log) => [
                'channel' => $log->channel,
                'recipient' => $log->recipient,
                'recipient_role' => $log->recipient_role,
                'status' => $log->status,
                'http_status' => $log->http_status,
                'message' => $log->message,
                'waktu' => optional($log->created_at)->format('Y-m-d H:i'),
            ])
            ->values()
            ->all();
    }

    private function buildAiDocumentAttachments($santriRows, string $question): array
    {
        if (!$this->questionNeedsDocumentVision($question)) {
            return [];
        }

        $wantedFields = $this->wantedAiDocumentFields($question);
        $attachments = [];

        foreach ($santriRows->take(3) as $santri) {
            foreach ($this->aiDocumentLabels() as $field => $label) {
                if (!empty($wantedFields) && !in_array($field, $wantedFields, true)) {
                    continue;
                }

                $path = $santri->{$field};
                if (!$path || !Storage::disk('public')->exists($path)) {
                    continue;
                }

                $dataUrl = $this->compressedImageDataUrl(Storage::disk('public')->path($path));
                if (!$dataUrl) {
                    continue;
                }

                $attachments[] = [
                    'owner' => $santri->nama_lengkap . ' (' . ($santri->nomor_pendaftaran ?? 'tanpa nomor') . ')',
                    'label' => $label,
                    'url' => $dataUrl,
                ];

                if (count($attachments) >= 8) {
                    return $attachments;
                }
            }
        }

        return $attachments;
    }

    private function questionNeedsDocumentVision(string $question): bool
    {
        $question = Str::lower($question);

        return Str::contains($question, [
            'berkas', 'dokumen', 'upload', 'unggah', 'ktp', 'kk', 'kartu keluarga',
            'akta', 'foto', 'pas foto', 'isi file', 'isi gambar', 'valid', 'terbaca',
            'cocok', 'sesuai', 'lampiran',
        ]);
    }

    private function wantedAiDocumentFields(string $question): array
    {
        $question = Str::lower($question);
        $fields = [];

        if (Str::contains($question, ['ktp ayah', 'ktp bapak', 'ayah', 'bapak'])) {
            $fields[] = 'foto_ktp_ayah';
        }
        if (Str::contains($question, ['ktp ibu', 'ibu'])) {
            $fields[] = 'foto_ktp_ibu';
        }
        if (Str::contains($question, ['akta', 'kelahiran'])) {
            $fields[] = 'foto_akta_anak';
        }
        if (Str::contains($question, ['kk', 'kartu keluarga'])) {
            $fields[] = 'foto_kk';
        }
        if (Str::contains($question, ['pas foto', 'foto anak', 'foto siswa', 'portrait'])) {
            $fields[] = 'foto_pas_siswa';
        }

        return array_values(array_unique($fields));
    }

    private function compressedImageDataUrl(string $sourcePath): ?string
    {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return null;
        }

        $image = match ($info['mime'] ?? '') {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$image) {
            return null;
        }

        $maxSide = 1400;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxSide || $height > $maxSide) {
            $ratio = min($maxSide / $width, $maxSide / $height);
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            $bg = imagecolorallocate($newImage, 255, 255, 255);
            imagefill($newImage, 0, 0, $bg);
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        ob_start();
        imagejpeg($image, null, 72);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (!$binary) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($binary);
    }

    private function aiDocumentLabels(): array
    {
        return [
            'foto_ktp_ayah' => 'KTP Ayah',
            'foto_ktp_ibu' => 'KTP Ibu',
            'foto_akta_anak' => 'Akta Kelahiran Anak',
            'foto_kk' => 'Kartu Keluarga',
            'foto_pas_siswa' => 'Pas Foto Siswa',
            'tanda_tangan' => 'Tanda Tangan',
        ];
    }

    private function aiFormFieldLabels(): array
    {
        return [
            'nomor_pendaftaran' => 'Nomor pendaftaran',
            'nama_lengkap' => 'Nama lengkap',
            'jenis_kelamin' => 'Jenis kelamin',
            'nisn' => 'NISN',
            'no_seri_ijazah' => 'Nomor seri ijazah',
            'no_seri_skhun' => 'Nomor seri SKHUN',
            'no_ujian_nasional' => 'Nomor ujian nasional',
            'nik' => 'NIK anak',
            'nama_sekolah_asal' => 'Sekolah asal',
            'npsn_sekolah_asal' => 'NPSN sekolah asal',
            'alamat_sekolah_asal' => 'Alamat sekolah asal',
            'tempat_lahir' => 'Tempat lahir anak',
            'tanggal_lahir' => 'Tanggal lahir anak',
            'agama' => 'Agama',
            'berkebutuhan_khusus' => 'Kebutuhan khusus anak',
            'alamat_lengkap' => 'Alamat lengkap',
            'dusun' => 'Dusun',
            'rt_rw' => 'RT/RW',
            'kelurahan_desa' => 'Kelurahan/desa',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten/kota',
            'propinsi' => 'Propinsi',
            'kode_pos' => 'Kode pos',
            'alat_transportasi' => 'Alat transportasi',
            'jenis_tinggal' => 'Jenis tinggal',
            'no_telepon_rumah' => 'Telepon rumah',
            'email' => 'Email anak/keluarga',
            'hobi' => 'Hobi',
            'nama_ayah' => 'Nama ayah',
            'nik_ayah' => 'NIK ayah',
            'tempat_lahir_ayah' => 'Tempat lahir ayah',
            'tanggal_lahir_ayah' => 'Tanggal lahir ayah',
            'berkebutuhan_khusus_ayah' => 'Kebutuhan khusus ayah',
            'pekerjaan_ayah' => 'Pekerjaan ayah',
            'pendidikan_ayah' => 'Pendidikan ayah',
            'no_wa_ayah' => 'WhatsApp ayah',
            'penghasilan_ayah' => 'Penghasilan ayah',
            'alamat_ayah' => 'Alamat ayah',
            'rt_rw_ayah' => 'RT/RW ayah',
            'kelurahan_desa_ayah' => 'Kelurahan/desa ayah',
            'kecamatan_ayah' => 'Kecamatan ayah',
            'status_tahsin_ayah' => 'Status tahsin ayah',
            'pengajar_tahsin_ayah' => 'Pengajar tahsin ayah',
            'nama_ibu' => 'Nama ibu',
            'nik_ibu' => 'NIK ibu',
            'tempat_lahir_ibu' => 'Tempat lahir ibu',
            'tanggal_lahir_ibu' => 'Tanggal lahir ibu',
            'berkebutuhan_khusus_ibu' => 'Kebutuhan khusus ibu',
            'pekerjaan_ibu' => 'Pekerjaan ibu',
            'pendidikan_ibu' => 'Pendidikan ibu',
            'no_wa_ibu' => 'WhatsApp ibu',
            'penghasilan_ibu' => 'Penghasilan ibu',
            'alamat_ibu' => 'Alamat ibu',
            'rt_rw_ibu' => 'RT/RW ibu',
            'kelurahan_desa_ibu' => 'Kelurahan/desa ibu',
            'kecamatan_ibu' => 'Kecamatan ibu',
            'status_tahsin_ibu' => 'Status tahsin ibu',
            'pengajar_tahsin_ibu' => 'Pengajar tahsin ibu',
            'nama_wali' => 'Nama wali',
            'nik_wali' => 'NIK wali',
            'tempat_lahir_wali' => 'Tempat lahir wali',
            'tanggal_lahir_wali' => 'Tanggal lahir wali',
            'tahun_lahir_wali' => 'Tahun lahir wali',
            'berkebutuhan_khusus_wali' => 'Kebutuhan khusus wali',
            'pekerjaan_wali' => 'Pekerjaan wali',
            'pendidikan_wali' => 'Pendidikan wali',
            'no_wa_wali' => 'WhatsApp wali',
            'penghasilan_wali' => 'Penghasilan wali',
            'alamat_wali' => 'Alamat wali',
            'rt_rw_wali' => 'RT/RW wali',
            'kelurahan_desa_wali' => 'Kelurahan/desa wali',
            'kecamatan_wali' => 'Kecamatan wali',
            'status_tahsin_wali' => 'Status tahsin wali',
            'pengajar_tahsin_wali' => 'Pengajar tahsin wali',
            'email_ayah' => 'Email ayah',
            'email_ibu' => 'Email ibu',
            'email_wali' => 'Email wali',
            'tinggi_badan' => 'Tinggi badan',
            'berat_badan' => 'Berat badan',
            'jarak_ke_sekolah' => 'Jarak ke sekolah',
            'waktu_tempuh' => 'Waktu tempuh',
            'jumlah_saudara_kandung' => 'Jumlah saudara kandung',
            'punya_saudara_di_sini' => 'Punya saudara di sini',
            'siblings_data' => 'Data saudara',
            'status_pendaftaran' => 'Status pendaftaran',
            'dokumen_status' => 'Status dokumen',
            'dokumen_catatan' => 'Catatan dokumen',
            'followup_sudah_masuk_grup' => 'Sudah masuk grup',
            'followup_sudah_dihubungi' => 'Sudah dihubungi',
            'followup_catatan' => 'Catatan follow-up',
            'created_at' => 'Tanggal input',
            'updated_at' => 'Terakhir diperbarui',
        ];
    }
}

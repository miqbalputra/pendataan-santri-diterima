<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\Periode;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        
        // Pengaturan Direct AI
        $ai_endpoint = Setting::where('key', 'ai_endpoint')->value('value') ?? 'https://api.openai.com/v1/chat/completions';
        $ai_api_key = Setting::where('key', 'ai_api_key')->value('value') ?? '';
        $ai_model = Setting::where('key', 'ai_model')->value('value') ?? 'gpt-4o';
        
        $app_locked = Setting::where('key', 'app_locked')->value('value') == '1';
        
        $kop_baris_1 = Setting::where('key', 'kop_baris_1')->value('value') ?? 'GRIYA QUR\'AN BAITUL MANSHURIN';
        $kop_baris_2 = Setting::where('key', 'kop_baris_2')->value('value') ?? 'Sistem Pendaftaran Peserta Didik Baru (SPSB)';
        $kop_baris_3 = Setting::where('key', 'kop_baris_3')->value('value') ?? 'Jl. Contoh No. 123, Kota ABC, Propinsi XYZ | Telp: 0812-3456-7890';

        $periodes = Periode::all();
        $logs = ActivityLog::latest()->take(100)->get();

        return view('admin.dashboard', compact('pendaftar', 'ocr_engine', 'ocr_webhook_url', 'stats', 'ai_endpoint', 'ai_api_key', 'ai_model', 'app_locked', 'periodes', 'logs', 'kop_baris_1', 'kop_baris_2', 'kop_baris_3'));
    }

    public function updateSettings(Request $request) {
        Setting::updateOrCreate(['key' => 'ocr_engine'], ['value' => $request->ocr_engine]);
        Setting::updateOrCreate(['key' => 'ocr_webhook_url'], ['value' => $request->ocr_webhook_url]);
        
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
        $santri = CalonSantri::findOrFail($id);
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
            'aktivitas' => "Edit Data Pendaftar: {$santri->nama_lengkap}",
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
        $santri->update(['status_pendaftaran' => $request->status_pendaftaran]);
        
        ActivityLog::create([
            'aktivitas' => "Verifikasi Status: {$santri->nama_lengkap} -> {$request->status_pendaftaran}",
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'Status berhasil diperbarui!');
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
    
    public function exportData(Request $request) {
        $format = $request->format;
        $data = CalonSantri::orderBy('id')->get();
        
        ActivityLog::create([
            'aktivitas' => 'Export Data Pendaftar ('.$format.')',
            'aktor' => 'Admin',
            'ip_address' => $request->ip()
        ]);
        
        if ($format == 'csv') {
            $fileName = 'Data_Pendaftar_SPSB.csv';
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
            'Content-Disposition' => 'attachment; filename="Data_Pendaftar_SPSB_Full.xls"',
            'Cache-Control' => 'max-age=0',
        ];

        $callback = function () use ($data, $columns) {
            echo "\xEF\xBB\xBF";
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
            echo 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
            echo 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
            echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Worksheet ss:Name="Data Pendaftar"><Table>';

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

        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . 'berkas-pendaftaran-' . now()->format('YmdHis') . '-' . uniqid() . '.zip';
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
            'Berkas_Pendaftaran_SPSB.zip',
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

        $pendaftar = CalonSantri::select('nama_lengkap', 'jenis_kelamin', 'nama_ayah', 'no_wa_ayah', 'status_pendaftaran', 'created_at')->get();
        
        $context = "Kamu adalah Asisten AI untuk Administrator Sekolah SPSB. Berikut adalah data pendaftar terbaru dalam format JSON:\n";
        $context .= $pendaftar->toJson() . "\n\n";
        $context .= "Gunakan data di atas untuk menjawab pertanyaan admin secara profesional dan akurat. Jika data tidak ada, katakan sejujurnya.";

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $context],
                    ['role' => 'user', 'content' => $question]
                ],
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $answer = $response->json('choices.0.message.content');
                return response()->json(['success' => true, 'answer' => $answer]);
            }

            $errorBody = $response->json('error.message') ?? $response->json('message') ?? $response->body();
            return response()->json(['success' => false, 'error' => 'AI Error [' . $response->status() . ']: ' . $errorBody]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Koneksi Gagal: ' . $e->getMessage()]);
        }
    }
}

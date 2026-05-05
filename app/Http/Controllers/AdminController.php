<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\Periode;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
            
            // Standard OpenAI models endpoint
            $modelsEndpoint = str_replace('/chat/completions', '/models', $endpoint);
            
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)->get($modelsEndpoint);
            
            if ($response->successful()) {
                $models = collect($response->json('data'))->pluck('id')->toArray();
                return response()->json(['success' => true, 'models' => $models]);
            }
            return response()->json(['success' => false, 'error' => 'API Error: ' . $response->body()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
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
        $santri->update($request->all());
        return redirect()->route('admin.dashboard')->with('success', 'Data diperbarui!');
    }

    public function updateStatus(Request $request, $id) {
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
        $data = CalonSantri::all();
        
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
                        $row->id, $row->nama_lengkap, $row->nik_anak, $row->nama_ayah, $row->no_wa_ayah, $row->status_pendaftaran
                    ]);
                }
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        }
        
        // Return HTML for printing (PDF)
        return view('admin.print_all', compact('data'));
    }
}
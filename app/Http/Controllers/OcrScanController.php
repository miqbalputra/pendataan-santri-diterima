<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOcrScan;
use App\Models\OcrScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OcrScanController extends Controller
{
    private const TARGETS = ['akta', 'kk', 'ayah', 'ibu', 'foto'];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'target' => ['required', 'in:' . implode(',', self::TARGETS)],
            'run_ocr' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $imageInfo = @getimagesize($file->getRealPath());
        if (!$imageInfo || ($imageInfo[0] * $imageInfo[1]) > 40_000_000) {
            throw ValidationException::withMessages(['file' => 'Ukuran gambar terlalu besar untuk diproses. Ambil foto ulang dengan resolusi lebih rendah.']);
        }

        $id = (string) Str::uuid();
        $token = bin2hex(random_bytes(32));
        $extension = strtolower($file->guessExtension() ?: 'jpg');
        $path = $file->storeAs('ocr-staging', $id . '.' . $extension, 'local');
        if (!$path) {
            return response()->json(['message' => 'Foto belum dapat disimpan. Silakan coba lagi atau isi data manual.'], 503);
        }

        $shouldRunOcr = (bool) ($validated['run_ocr'] ?? true) && $validated['target'] !== 'foto';
        $queueFull = $shouldRunOcr
            && OcrScan::whereIn('status', ['queued', 'processing'])->count() >= max(1, (int) config('services.ocr.queue_limit', 30));
        $queued = $shouldRunOcr && !$queueFull;
        $scan = OcrScan::create([
            'id' => $id,
            'token_hash' => hash('sha256', $token),
            'session_hash' => hash('sha256', $request->session()->getId()),
            'target' => $validated['target'],
            'status' => $queued ? 'queued' : ($queueFull ? 'failed' : 'ready'),
            'stage' => $queued ? 'queued' : ($queueFull ? 'manual' : 'complete'),
            'progress' => $queued ? 0 : 100,
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'result' => $queued ? null : [
                'document_type' => $validated['target'] === 'foto' ? 'photo' : 'unknown',
                'document_check' => ['is_expected_document' => !$queueFull],
                'fields' => [],
                'warnings' => $queueFull
                    ? ['Antrean OCR sedang penuh. Foto tetap tersimpan; silakan isi data secara manual.']
                    : [],
            ],
            'expires_at' => now()->addHours(24),
        ]);

        if ($queued) {
            try {
                ProcessOcrScan::dispatch($scan->id)->onConnection('database')->onQueue('ocr');
            } catch (\Throwable) {
                $scan->forceFill([
                    'status' => 'failed', 'stage' => 'manual', 'progress' => 100,
                    'result' => [
                        'document_type' => 'unknown',
                        'document_check' => ['is_expected_document' => false],
                        'fields' => [],
                        'warnings' => ['Layanan OCR sedang tidak tersedia. Foto tetap terlampir; silakan isi data manual.'],
                    ],
                ])->save();
            }
        }

        return response()->json([
            'id' => $scan->id,
            'scan_token' => $token,
            'status' => $scan->status,
            'stage' => $scan->stage,
            'progress' => $scan->progress,
            'expires_at' => $scan->expires_at->toIso8601String(),
        ], 202);
    }

    public function show(Request $request, string $id)
    {
        $scan = $this->authorizedScan($request, $id);
        if ($scan->expires_at->isPast()) {
            $this->discard($scan);
            return response()->json(['message' => 'Sesi foto sudah berakhir. Silakan pilih foto kembali.'], 410);
        }

        return response()->json([
            'id' => $scan->id,
            'status' => $scan->status,
            'stage' => $scan->stage,
            'progress' => (int) $scan->progress,
            'result' => in_array($scan->status, ['ready', 'failed'], true) ? $scan->result : null,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $scan = $this->authorizedScan($request, $id);
        if ($scan->used_at) {
            return response()->json(['message' => 'Lampiran sudah digunakan.'], 409);
        }

        $this->discard($scan);
        return response()->json(['deleted' => true]);
    }

    private function authorizedScan(Request $request, string $id): OcrScan
    {
        abort_unless((bool) preg_match('/^[0-9a-f-]{36}$/i', $id), 404);
        $token = (string) $request->header('X-OCR-Token', '');
        abort_unless((bool) preg_match('/^[0-9a-f]{64}$/', $token), 404);

        $scan = OcrScan::find($id);
        abort_unless($scan
            && hash_equals($scan->token_hash, hash('sha256', $token))
            && hash_equals($scan->session_hash, hash('sha256', $request->session()->getId())), 404);

        return $scan;
    }

    private function discard(OcrScan $scan): void
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($scan->file_path) || $disk->delete($scan->file_path)) {
            $scan->delete();
        }
    }

    public function rejectLegacyCloud(Request $request)
    {
        return response()->json([
            'message' => 'OCR cloud dinonaktifkan. Muat ulang halaman untuk menggunakan OCR lokal.',
        ], 410);
    }
}

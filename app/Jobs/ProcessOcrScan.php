<?php

namespace App\Jobs;

use App\Models\OcrScan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessOcrScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 55;

    public function __construct(public string $scanId)
    {
        $this->onQueue('ocr');
    }

    public function handle(): void
    {
        $scan = OcrScan::find($this->scanId);
        if (!$scan || $scan->status !== 'queued' || $scan->expires_at->isPast() || $scan->used_at) {
            return;
        }

        $disk = Storage::disk('local');
        $path = $disk->path($scan->file_path);
        if (!is_file($path)) {
            $this->markFailed($scan);
            return;
        }

        $scan->forceFill(['status' => 'processing', 'stage' => 'preparing', 'progress' => 0])->save();
        $stream = @fopen($path, 'rb');
        if (!$stream) {
            $this->markFailed($scan);
            return;
        }

        try {
            $scan->forceFill(['stage' => 'recognizing', 'progress' => 0])->save();
            $response = Http::acceptJson()
                ->withToken((string) config('services.ocr.token'))
                ->timeout((int) config('services.ocr.timeout', 45))
                ->attach('file', $stream, basename($path), ['Content-Type' => $scan->mime_type])
                ->post(rtrim((string) config('services.ocr.url'), '/') . '/internal/ocr', [
                    'target' => $scan->target,
                ]);

            if (!$response->successful()) {
                $this->markFailed($scan);
                return;
            }

            $payload = $response->json();
            if (!is_array($payload) || !is_array($payload['fields'] ?? null)) {
                $this->markFailed($scan);
                return;
            }

            // Persist only the structured fields needed by the form, never raw OCR text.
            $result = [
                'document_type' => is_string($payload['document_type'] ?? null) ? $payload['document_type'] : 'unknown',
                'document_check' => is_array($payload['document_check'] ?? null) ? $payload['document_check'] : [],
                'fields' => $payload['fields'],
                'warnings' => array_values(array_filter($payload['warnings'] ?? [], 'is_string')),
            ];

            $scan->forceFill([
                'status' => 'ready',
                'stage' => 'complete',
                'progress' => 100,
                'result' => $result,
            ])->save();
        } catch (Throwable $exception) {
            // Do not put document contents, provider payloads, or exception messages in logs.
            Log::warning('Local OCR scan failed.', ['scan_id' => $scan->id, 'exception' => $exception::class]);
            $this->markFailed($scan);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($scan = OcrScan::find($this->scanId)) {
            $this->markFailed($scan);
        }
    }

    private function markFailed(OcrScan $scan): void
    {
        $scan->forceFill([
            'status' => 'failed',
            'stage' => 'manual',
            'progress' => 100,
            'result' => [
                'document_type' => 'unknown',
                'document_check' => ['is_expected_document' => false],
                'fields' => [],
                'warnings' => ['Foto tetap terlampir. Isi data secara manual atau coba foto ulang.'],
            ],
        ])->save();
    }
}

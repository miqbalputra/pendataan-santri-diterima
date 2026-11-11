<?php

namespace App\Console\Commands;

use App\Models\OcrScan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredOcrScans extends Command
{
    protected $signature = 'ocr:cleanup';
    protected $description = 'Remove temporary OCR scans after their 24-hour lifetime.';

    public function handle(): int
    {
        $removed = 0;
        OcrScan::where('expires_at', '<=', now())->chunkById(100, function ($scans) use (&$removed) {
            foreach ($scans as $scan) {
                $disk = Storage::disk('local');
                if (!$disk->exists($scan->file_path) || $disk->delete($scan->file_path)) {
                    $scan->delete();
                    $removed++;
                }
            }
        }, 'id');

        $this->info("Removed {$removed} expired OCR scans.");
        return self::SUCCESS;
    }
}

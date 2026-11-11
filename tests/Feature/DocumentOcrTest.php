<?php

namespace Tests\Feature;

use App\Models\CalonSantri;
use App\Models\OcrScan;
use App\Jobs\ProcessOcrScan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentOcrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'file']);
        $this->withCredentials();
    }

    public function test_manual_registration_still_requires_all_five_attachments(): void
    {
        $this->postJson('/pendaftaran', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['foto_akta_anak', 'foto_kk', 'foto_ktp_ayah', 'foto_ktp_ibu', 'foto_pas_siswa']);
    }

    public function test_registration_allows_camera_only_for_same_origin(): void
    {
        $this->get('/')->assertOk()->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=()');
    }

    public function test_ocr_image_is_staged_privately_and_token_is_bound_to_session(): void
    {
        Storage::fake('local');
        $response = $this->postJson('/ocr/scans', [
            'file' => UploadedFile::fake()->image('ktp.jpg', 1500, 950),
            'target' => 'ayah', 'run_ocr' => '0',
        ])->assertAccepted()->assertJsonStructure(['id', 'scan_token', 'status', 'stage', 'progress']);

        $id = $response->json('id');
        $token = $response->json('scan_token');
        $scan = OcrScan::findOrFail($id);
        Storage::disk('local')->assertExists($scan->file_path);
        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
        $this->getJson("/ocr/scans/{$id}", ['X-OCR-Token' => $token])->assertOk()->assertJsonPath('status', 'ready');

        $scan->forceFill(['session_hash' => hash('sha256', 'another-session')])->save();
        $this->getJson("/ocr/scans/{$id}", ['X-OCR-Token' => $token])->assertNotFound();
    }

    public function test_ocr_job_uses_the_private_database_queue_without_cloud_requests(): void
    {
        Storage::fake('local');
        Http::preventStrayRequests();
        $response = $this->postJson('/ocr/scans', [
            'file' => UploadedFile::fake()->image('ktp.jpg', 1500, 950),
            'target' => 'ayah', 'run_ocr' => '1',
        ])->assertAccepted()->assertJsonPath('status', 'queued');

        $this->assertDatabaseHas('jobs', ['queue' => 'ocr']);
        $this->assertDatabaseHas('ocr_scans', ['id' => $response->json('id'), 'status' => 'queued']);
        Http::assertNothingSent();
    }

    public function test_full_ocr_queue_keeps_photo_for_manual_entry(): void
    {
        Storage::fake('local');
        config(['services.ocr.queue_limit' => 2]);
        foreach (range(1, 2) as $index) {
            OcrScan::create([
                'id' => (string) Str::uuid(),
                'token_hash' => hash('sha256', 'queued-token-' . $index),
                'session_hash' => hash('sha256', 'queued-session-' . $index),
                'target' => 'ayah', 'status' => 'queued', 'stage' => 'queued', 'progress' => 0,
                'file_path' => 'ocr-staging/queued-' . $index . '.jpg', 'mime_type' => 'image/jpeg',
                'expires_at' => now()->addHour(),
            ]);
        }

        $response = $this->postJson('/ocr/scans', [
            'file' => UploadedFile::fake()->image('ktp.jpg', 1500, 950),
            'target' => 'ayah', 'run_ocr' => '1',
        ])->assertAccepted()->assertJsonPath('status', 'failed');
        $scan = OcrScan::findOrFail($response->json('id'));
        Storage::disk('local')->assertExists($scan->file_path);
        $this->assertDatabaseMissing('jobs', ['queue' => 'ocr']);

        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
        $this->getJson('/ocr/scans/' . $scan->id, ['X-OCR-Token' => $response->json('scan_token')])
            ->assertOk()->assertJsonPath('result.warnings.0', 'Antrean OCR sedang penuh. Foto tetap tersimpan; silakan isi data secara manual.');
    }

    public function test_ocr_worker_stores_only_structured_fields_and_keeps_staging_on_failure(): void
    {
        Storage::fake('local');
        config(['services.ocr.token' => 'private-test-token']);
        $response = $this->postJson('/ocr/scans', [
            'file' => UploadedFile::fake()->image('ktp.jpg', 1500, 950),
            'target' => 'ayah', 'run_ocr' => '1',
        ])->assertAccepted();
        $scan = OcrScan::findOrFail($response->json('id'));
        $path = $scan->file_path;

        Http::fakeSequence('http://ocr:8000/internal/ocr')->push([
            'document_type' => 'ktp',
            'document_check' => ['is_expected_document' => true, 'confidence' => 'high'],
            'fields' => ['nik' => ['value' => '1234567890123456', 'confidence' => 0.99, 'decision' => 'confirm']],
            'warnings' => [], 'extracted_text' => 'RAW DOCUMENT TEXT MUST NOT BE STORED',
        ])->push([], 503);

        (new ProcessOcrScan($scan->id))->handle();
        $scan->refresh();
        $this->assertSame('ready', $scan->status);
        $this->assertSame('1234567890123456', $scan->result['fields']['nik']['value']);
        $this->assertStringNotContainsString('RAW DOCUMENT TEXT', json_encode($scan->result));
        Storage::disk('local')->assertExists($path);
        Http::assertSent(fn ($request) => $request->url() === 'http://ocr:8000/internal/ocr'
            && $request->hasHeader('Authorization', 'Bearer private-test-token'));

        $scan->forceFill(['status' => 'queued', 'stage' => 'queued', 'result' => null])->save();
        (new ProcessOcrScan($scan->id))->handle();
        $this->assertSame('failed', $scan->fresh()->status);
        Storage::disk('local')->assertExists($path);
    }

    public function test_old_ocr_route_is_disabled_without_calling_any_provider(): void
    {
        Http::preventStrayRequests();
        $this->postJson('/upload-ocr', [])->assertGone();
        Http::assertNothingSent();
    }

    public function test_staged_attachment_moves_to_registration_after_success(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $scanResponse = $this->postJson('/ocr/scans', [
            'file' => UploadedFile::fake()->image('foto-anak.jpg', 900, 1200),
            'target' => 'foto', 'run_ocr' => '0',
        ])->assertAccepted();
        $scan = OcrScan::findOrFail($scanResponse->json('id'));
        $token = $scanResponse->json('scan_token');
        $this->withCookie(config('session.cookie'), $scanResponse->getCookie(config('session.cookie'))->getValue());

        $input = $this->registrationInput();
        unset($input['foto_pas_siswa']);
        $input['staged_files'] = ['foto_pas_siswa' => $token];
        $this->postJson('/pendaftaran', $input)->assertOk()->assertJsonPath('saved', true);

        $santri = CalonSantri::firstOrFail();
        Storage::disk('public')->assertExists($santri->foto_pas_siswa);
        Storage::disk('local')->assertMissing($scan->file_path);
        $this->assertDatabaseMissing('ocr_scans', ['id' => $scan->id]);
    }

    public function test_staged_file_remains_available_when_registration_validation_fails(): void
    {
        Storage::fake('local');
        $scanResponse = $this->postJson('/ocr/scans', [
            'file' => UploadedFile::fake()->image('foto-anak.jpg', 900, 1200),
            'target' => 'foto', 'run_ocr' => '0',
        ])->assertAccepted();
        $scan = OcrScan::findOrFail($scanResponse->json('id'));

        $input = $this->registrationInput();
        unset($input['foto_pas_siswa']);
        $input['staged_files'] = ['foto_pas_siswa' => $scanResponse->json('scan_token')];
        $input['nik'] = 'invalid';
        $this->postJson('/pendaftaran', $input)->assertUnprocessable()->assertJsonValidationErrors('nik');

        $this->assertDatabaseHas('ocr_scans', ['id' => $scan->id, 'used_at' => null]);
        Storage::disk('local')->assertExists($scan->file_path);
    }

    public function test_staging_token_cannot_be_used_for_another_document_type(): void
    {
        Storage::fake('local');
        $scanResponse = $this->postJson('/ocr/scans', [
            'file' => UploadedFile::fake()->image('kk.jpg', 1200, 1600),
            'target' => 'kk', 'run_ocr' => '0',
        ])->assertAccepted();
        $scan = OcrScan::findOrFail($scanResponse->json('id'));

        $input = $this->registrationInput();
        unset($input['foto_pas_siswa']);
        $input['staged_files'] = ['foto_pas_siswa' => $scanResponse->json('scan_token')];
        $this->postJson('/pendaftaran', $input)->assertUnprocessable()->assertJsonValidationErrors('staged_files.foto_pas_siswa');
        Storage::disk('local')->assertExists($scan->file_path);
    }

    private function registrationInput(): array
    {
        $signature = UploadedFile::fake()->image('signature.png', 120, 60);
        $data = [
            'nama_lengkap' => 'Contoh Anak', 'jenis_kelamin' => 'Perempuan', 'nik' => '1234567890123456',
            'tempat_lahir' => 'Contoh Kota', 'tanggal_lahir' => '2017-01-01', 'agama' => 'Islam',
            'alamat_lengkap' => 'Jalan Contoh', 'rt_rw' => '008/002', 'kelurahan_desa' => 'Desa Contoh',
            'kecamatan' => 'Contoh', 'kabupaten_kota' => 'Contoh', 'propinsi' => 'Contoh', 'kode_pos' => '12345',
            'jenis_tinggal' => 'Bersama orang tua', 'alat_transportasi' => 'Jalan kaki',
            'nama_ayah' => 'Contoh Bapak', 'nik_ayah' => '1234567890123457', 'no_wa_ayah' => '081234567890',
            'nama_ibu' => 'Contoh Ibu', 'nik_ibu' => '1234567890123458', 'no_wa_ibu' => '081234567891',
            'penandatangan_nama' => 'Contoh Ibu', 'pernyataan_kebenaran_data' => 'on',
            'tanda_tangan_base64' => 'data:image/png;base64,' . base64_encode(file_get_contents($signature->getRealPath())),
        ];
        foreach (['foto_akta_anak', 'foto_kk', 'foto_ktp_ayah', 'foto_ktp_ibu', 'foto_pas_siswa'] as $field) {
            $data[$field] = UploadedFile::fake()->image($field . '.jpg');
        }
        return $data;
    }
}

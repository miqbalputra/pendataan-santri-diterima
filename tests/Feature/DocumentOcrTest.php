<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentOcrTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_registration_still_requires_all_five_attachments(): void
    {
        $this->postJson('/pendaftaran', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['foto_akta_anak', 'foto_kk', 'foto_ktp_ayah', 'foto_ktp_ibu', 'foto_pas_siswa']);
    }

    public function test_registration_allows_camera_only_for_same_origin(): void
    {
        $this->get('/')->assertOk()->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=()');
    }

    public function test_unclear_or_unknown_documents_never_autofill_identity(): void
    {
        $this->directAi();
        foreach ([['document_type' => 'unknown', 'confidence' => 'high'], ['document_type' => 'ktp', 'confidence' => 'high', 'image_quality' => 'poor']] as $fields) {
            Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode($fields + ['is_expected_document' => true, 'nik' => '1234567890123456'])]]]])]);
            $this->postJson('/upload-ocr', ['file' => UploadedFile::fake()->image('ktp.jpg', 1500, 950), 'target' => 'ayah'])
                ->assertOk()->assertJsonPath('fields', []);
        }
    }

    public function test_clear_ktp_uses_high_detail_and_preserves_structured_fields(): void
    {
        $this->directAi();
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'document_type' => 'ktp', 'confidence' => 'high', 'is_expected_document' => true,
            'detected_gender' => 'Laki-laki', 'image_quality' => 'good', 'nama' => 'CONTOH BAPAK', 'nik' => '1234567890123456',
        ])]]]])]);
        $this->postJson('/upload-ocr', ['file' => UploadedFile::fake()->image('ktp.jpg', 3000, 1900), 'target' => 'ayah'])
            ->assertOk()->assertJsonPath('fields.nama', 'CONTOH BAPAK')->assertJsonPath('document_check.is_expected_document', true);
        Http::assertSent(function ($request) {
            $image = $request['messages'][0]['content'][1]['image_url'];
            $dimensions = getimagesizefromstring(base64_decode(explode(',', $image['url'], 2)[1]));
            return $image['detail'] === 'high' && $dimensions[0] === 2400;
        });
    }

    public function test_n8n_receives_document_target(): void
    {
        Setting::updateOrCreate(['key' => 'ocr_engine'], ['value' => 'n8n']);
        Setting::updateOrCreate(['key' => 'ocr_webhook_url'], ['value' => 'https://ocr.example.test/webhook']);
        Http::fake(['*' => Http::response(['fields' => ['alamat' => 'JALAN CONTOH']])]);
        $this->postJson('/upload-ocr', ['file' => UploadedFile::fake()->image('kk.jpg'), 'target' => 'kk'])
            ->assertOk()->assertJsonPath('fields.alamat', 'JALAN CONTOH');
        Http::assertSent(fn ($request) => collect($request->data())->contains(
            fn ($part) => ($part['name'] ?? null) === 'target' && ($part['contents'] ?? null) === 'kk'
        ));
    }

    private function directAi(): void
    {
        foreach (['ocr_engine' => 'direct_ai', 'ai_api_key' => 'fake-key', 'ai_endpoint' => 'https://ocr.example.test/v1/chat/completions'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

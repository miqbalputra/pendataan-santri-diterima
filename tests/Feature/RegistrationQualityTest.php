<?php

namespace Tests\Feature;

use App\Models\CalonSantri;
use App\Models\Setting;
use App\Models\Gelombang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationQualityTest extends TestCase
{
    use RefreshDatabase;

    private int $webhookStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::fake(fn () => Http::response([], $this->webhookStatus));
    }

    private function input(): array
    {
        $signature = UploadedFile::fake()->image('signature.png', 120, 60);
        $data = [
            'nama_lengkap' => '  Contoh   Anak  ', 'jenis_kelamin' => 'Perempuan', 'nik' => '1234567890123456',
            'tempat_lahir' => 'Contoh Kota', 'tanggal_lahir' => '2017-01-01', 'agama' => 'Islam',
            'alamat_lengkap' => 'Jalan Contoh', 'rt_rw' => ' 008 / 002 ', 'kelurahan_desa' => 'Desa Contoh',
            'kecamatan' => 'Contoh', 'kabupaten_kota' => 'Contoh', 'propinsi' => 'Contoh', 'kode_pos' => '12345',
            'jenis_tinggal' => 'Bersama orang tua', 'alat_transportasi' => 'Jalan kaki',
            'nama_ayah' => 'Contoh Bapak', 'nik_ayah' => '1234567890123457', 'no_wa_ayah' => '+62 812-3456-7890',
            'nama_ibu' => 'Contoh Ibu', 'nik_ibu' => '1234567890123458', 'no_wa_ibu' => '081234567891',
            'penandatangan_nama' => 'Contoh Ibu', 'pernyataan_kebenaran_data' => 'on',
            'tanda_tangan_base64' => 'data:image/png;base64,' . base64_encode(file_get_contents($signature->getRealPath())),
        ];
        foreach (['foto_akta_anak', 'foto_kk', 'foto_ktp_ayah', 'foto_ktp_ibu', 'foto_pas_siswa'] as $field) {
            $data[$field] = UploadedFile::fake()->image($field . '.jpg');
        }
        return $data;
    }

    public function test_registration_normalizes_data_and_ignores_forged_verification_fields(): void
    {
        $input = $this->input() + ['status_pendaftaran' => 'Diterima', 'dokumen_catatan' => 'forged', 'revisi_token' => 'forged', 'followup_sudah_dihubungi' => true, 'tanda_tangan' => 'existing-file.png'];
        $this->postJson('/pendaftaran', $input)->assertOk()->assertJsonPath('saved', true)->assertJsonStructure(['redirect_url']);
        $santri = CalonSantri::firstOrFail();
        $this->assertSame('Contoh Anak', $santri->nama_lengkap);
        $this->assertSame('081234567890', $santri->no_wa_ayah);
        $this->assertSame('008/002', $santri->rt_rw);
        $this->assertSame('Pending', $santri->status_pendaftaran);
        $this->assertNull($santri->dokumen_catatan);
        $this->assertNotSame('forged', $santri->revisi_token);
        $this->assertFalse($santri->followup_sudah_dihubungi);
        Storage::disk('public')->assertExists($santri->foto_pas_siswa);
        Storage::disk('public')->assertExists($santri->tanda_tangan);
        $this->get('/pendaftaran/sukses')->assertOk()->assertSee('Contoh Anak');
    }

    public function test_invalid_identity_dates_and_contacts_are_rejected_before_storing_files(): void
    {
        $input = $this->input();
        $input['nik_ayah'] = $input['nik'];
        $input['nik_ibu'] = $input['nik'];
        $input['tanggal_lahir'] = '2099-02-30';
        $input['email_ayah'] = 'email-salah';
        $input['no_wa_ayah'] = '02112345678';
        $input['kode_pos'] = '123';
        $this->postJson('/pendaftaran', $input)->assertUnprocessable()
            ->assertJsonValidationErrors(['nik_ayah', 'nik_ibu', 'tanggal_lahir', 'email_ayah', 'no_wa_ayah', 'kode_pos']);
        $this->assertSame(0, CalonSantri::count());
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_duplicate_registration_keeps_existing_data_and_documents(): void
    {
        $this->postJson('/pendaftaran', $this->input())->assertOk();
        $existing = CalonSantri::firstOrFail();
        $before = $existing->getAttributes();
        $this->postJson('/pendaftaran', $this->input())->assertUnprocessable()->assertJsonValidationErrors('nik');
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $this->assertSame(1, CalonSantri::count());
        Storage::disk('public')->assertExists($existing->foto_akta_anak);
    }

    public function test_closed_registration_cannot_be_bypassed_by_posting_directly(): void
    {
        Setting::updateOrCreate(['key' => 'app_locked'], ['value' => '1']);
        $this->postJson('/pendaftaran', $this->input())->assertUnprocessable()->assertJsonValidationErrors('pendaftaran');
        $this->assertSame(0, CalonSantri::count());
    }

    public function test_impossible_measurements_and_parent_birth_dates_are_rejected(): void
    {
        $input = $this->input() + ['tinggi_badan' => '-1', 'berat_badan' => '0', 'jumlah_saudara_kandung' => '-3', 'tanggal_lahir_ayah' => '2020-01-01'];
        $this->postJson('/pendaftaran', $input)->assertUnprocessable()
            ->assertJsonValidationErrors(['tinggi_badan', 'berat_badan', 'jumlah_saudara_kandung', 'tanggal_lahir_ayah']);
        $this->assertSame(0, CalonSantri::count());
    }

    public function test_public_form_reads_settings_in_one_query(): void
    {
        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $queries = collect(DB::getQueryLog())->filter(fn ($entry) => str_contains($entry['query'], '"settings"'));
        $this->assertCount(1, $queries);
        DB::disableQueryLog();
    }

    public function test_full_quota_does_not_store_new_files_or_change_existing_registration(): void
    {
        Gelombang::create(['nama_gelombang' => 'Contoh', 'kuota' => 1, 'is_active' => true]);
        $this->postJson('/pendaftaran', $this->input())->assertOk();
        $files = Storage::disk('public')->allFiles();
        $next = $this->input();
        $next['nik'] = '1234567890123459';
        $this->postJson('/pendaftaran', $next)->assertUnprocessable()->assertJsonValidationErrors('gelombang');
        $this->assertSame(1, CalonSantri::count());
        $this->assertSame($files, Storage::disk('public')->allFiles());
    }

    public function test_webhook_failure_does_not_undo_saved_registration(): void
    {
        $this->withoutDefer();
        Setting::updateOrCreate(['key' => 'n8n_whatsapp_webhook_url'], ['value' => 'https://notification.example.test/webhook']);
        $this->webhookStatus = 503;
        $this->postJson('/pendaftaran', $this->input())->assertOk()->assertJsonPath('saved', true);
        $this->assertSame(1, CalonSantri::count());
        $this->assertDatabaseHas('notification_logs', ['channel' => 'whatsapp', 'status' => 'failed']);
    }

    public function test_dashboard_limits_followup_rows_and_keeps_search_on_pagination(): void
    {
        $rows = [];
        foreach (range(1, 105) as $index) {
            $rows[] = ['nama_lengkap' => 'Anak ' . $index, 'jenis_kelamin' => 'Perempuan', 'nik' => str_pad((string) $index, 16, '0', STR_PAD_LEFT), 'tempat_lahir' => 'Contoh', 'tanggal_lahir' => '2017-01-01', 'alamat_lengkap' => 'Contoh', 'nama_ayah' => 'Bapak', 'nama_ibu' => 'Ibu', 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('calon_santris')->insert($rows);
        $this->actingAs(User::factory()->create(['username' => 'test-admin']))->get('/admin/dashboard?search=Anak')
            ->assertOk()->assertViewHas('followUpPendaftar', fn ($rows) => $rows->count() === 25 && $rows->total() === 105)
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 105)
            ->assertSee('search=Anak', false);
    }
}

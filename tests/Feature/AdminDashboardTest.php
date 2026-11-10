<?php

namespace Tests\Feature;

use App\Models\CalonSantri;
use App\Models\Gelombang;
use App\Models\NotificationLog;
use App\Models\Periode;
use App\Models\User;
use App\Support\AdminRegistrationQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['username' => 'dashboard-admin']));
    }

    private function santri(array $overrides = []): CalonSantri
    {
        $id = ++$this->sequence;
        $santri = CalonSantri::create(array_merge([
            'nama_lengkap' => 'Peserta ' . $id, 'nik' => str_pad((string) $id, 16, '0', STR_PAD_LEFT),
            'nomor_pendaftaran' => 'SPSB-2026-' . $id, 'jenis_kelamin' => 'Perempuan',
            'tempat_lahir' => 'Contoh', 'tanggal_lahir' => '2017-01-01', 'alamat_lengkap' => 'Jalan Melati',
            'nama_ayah' => 'Bapak Contoh', 'nama_ibu' => 'Ibu Contoh', 'no_wa_ibu' => '081234567890',
            'status_pendaftaran' => 'Pending',
        ], $overrides));
        if (isset($overrides['created_at'])) $santri->forceFill(['created_at' => $overrides['created_at']])->save();
        return $santri->fresh();
    }

    private function ids(array $filters = []): array
    {
        $response = $this->get(route('admin.dashboard', $filters))->assertOk();
        return $response->viewData('pendaftar')->getCollection()->pluck('id')->all();
    }

    public function test_search_includes_registration_parent_identity_school_and_address(): void
    {
        $wanted = $this->santri(['nama_ibu' => 'Kirana Unik', 'nik_ibu' => '9988776655443322', 'nama_sekolah_asal' => 'Sekolah Pelangi', 'kabupaten_kota' => 'Sukamaju']);
        $other = $this->santri(['status_pendaftaran' => 'Diterima']);
        foreach (['Kirana', '9988776655443322', 'Pelangi', 'Sukamaju', $wanted->nomor_pendaftaran, 'Kirana Sukamaju'] as $search) {
            $this->assertSame([$wanted->id], $this->ids(['search' => $search]));
        }
        $this->assertSame([], $this->ids(['search' => 'Kirana', 'status_pendaftaran' => 'Diterima']));
    }

    public function test_formatted_international_phone_matches_local_contact(): void
    {
        $wanted = $this->santri();
        $this->assertSame([$wanted->id], $this->ids(['search' => '+62 812-3456-7890']));
    }

    public function test_search_metacharacters_do_not_match_every_registration(): void
    {
        $this->santri();
        $this->assertSame([], $this->ids(['search' => '%']));
        $this->assertSame([], $this->ids(['search' => '_']));
    }

    public function test_combined_filters_and_sort_order_match_selected_period(): void
    {
        $period = Periode::create(['nama_periode' => '2027', 'is_active' => true]);
        $wave = Gelombang::create(['nama_gelombang' => 'Gelombang 1', 'is_active' => true]);
        $first = $this->santri(['nama_lengkap' => 'Zahra', 'periode_id' => $period->id, 'gelombang_id' => $wave->id]);
        $second = $this->santri(['nama_lengkap' => 'Aisyah', 'periode_id' => $period->id, 'gelombang_id' => $wave->id]);
        $this->santri();
        $this->assertSame([$second->id, $first->id], $this->ids(['periode_id' => $period->id, 'gelombang_id' => $wave->id, 'jenis_kelamin' => 'Perempuan', 'sort' => 'name_asc']));
    }

    public function test_document_presence_and_verification_are_independent(): void
    {
        $paths = array_fill_keys(AdminRegistrationQuery::DOCUMENTS, 'pendaftaran/example.jpg');
        $complete = $this->santri($paths);
        $verified = $this->santri($paths + ['dokumen_status' => array_fill_keys(AdminRegistrationQuery::DOCUMENTS, 'valid')]);
        $missing = $this->santri(['dokumen_status' => array_fill_keys(AdminRegistrationQuery::DOCUMENTS, 'valid')]);
        $this->assertEqualsCanonicalizing([$complete->id, $verified->id], $this->ids(['dokumen' => 'present']));
        $this->assertSame([$verified->id], $this->ids(['dokumen' => 'verified']));
        $this->assertSame([$missing->id], $this->ids(['dokumen' => 'missing']));
        $this->assertSame([$complete->id], $this->ids(['dokumen' => 'review']));
        $this->assertSame(0, $missing->documentSummary()['valid']);
        $this->assertSame(5, $complete->documentSummary()['review']);
    }

    public function test_latest_notification_filter_ignores_older_failures(): void
    {
        $santri = $this->santri();
        foreach (['failed', 'success'] as $status) NotificationLog::create(['calon_santri_id' => $santri->id, 'channel' => 'whatsapp', 'status' => $status]);
        $this->assertSame([], $this->ids(['notifikasi' => 'failed']));
        $this->assertSame([$santri->id], $this->ids(['notifikasi' => 'success']));
    }

    public function test_date_range_uses_jakarta_day_boundaries(): void
    {
        $inside = $this->santri(['created_at' => '2026-11-09 17:30:00']); // 00:30 WIB, November 10.
        $this->santri(['created_at' => '2026-11-10 18:00:00']); // November 11 WIB.
        $this->assertSame([$inside->id], $this->ids(['dari' => '2026-11-10', 'sampai' => '2026-11-10']));
    }

    public function test_invalid_filters_are_rejected_and_never_change_data(): void
    {
        $santri = $this->santri();
        $before = $santri->getAttributes();
        $this->getJson(route('admin.dashboard', ['sort' => 'password', 'per_page' => 100000, 'search' => ['injected'], 'dari' => '2026-11-10', 'sampai' => '2026-01-01']))
            ->assertUnprocessable()->assertJsonValidationErrors(['sort', 'per_page', 'search', 'sampai']);
        $this->assertSame($before, $santri->fresh()->getAttributes());
    }

    public function test_csv_export_obeys_search_and_keeps_untrusted_text_inert(): void
    {
        $wanted = $this->santri(['nama_lengkap' => '=FORMULA', 'nama_ibu' => 'Ibu Target']);
        $this->santri(['nama_lengkap' => 'Peserta Lain']);
        $response = $this->get(route('admin.export', ['format' => 'csv', 'search' => 'Target']))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=FORMULA", $csv);
        $this->assertStringContainsString('Ibu Target', $csv);
        $this->assertStringNotContainsString('Peserta Lain', $csv);
        $this->assertStringNotContainsString('revisi_token', $csv);
    }

    public function test_profile_displays_previously_omitted_guardian_and_parent_fields(): void
    {
        $santri = $this->santri(['nama_wali' => 'Wali Contoh', 'email_wali' => 'wali@example.test', 'alamat_ayah' => 'Jalan Bapak Unik', 'pengajar_tahsin_wali' => 'Pengajar Contoh']);
        $this->get(route('admin.show', $santri->id))->assertOk()->assertSee('wali@example.test')->assertSee('Jalan Bapak Unik')->assertSee('Pengajar Contoh')->assertSee('Seluruh isian formulir');
    }

    public function test_dashboard_requires_authentication(): void
    {
        auth()->logout();
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_missing_document_cannot_be_marked_valid(): void
    {
        $santri = $this->santri();
        $this->postJson(route('admin.documents', $santri->id), ['dokumen_status' => ['foto_kk' => 'valid']])
            ->assertUnprocessable()->assertJsonValidationErrors('dokumen_status.foto_kk');
        $this->assertNull($santri->fresh()->dokumen_status);
    }

    public function test_detail_back_link_retains_search_and_filters(): void
    {
        $santri = $this->santri();
        $this->get(route('admin.show', ['id' => $santri->id, 'back' => 'search=Kirana&status_pendaftaran=Pending&page=2']))
            ->assertOk()->assertSee('search=Kirana&amp;status_pendaftaran=Pending&amp;page=2', false);
    }

    public function test_detail_notification_history_can_be_browsed_beyond_eight_records(): void
    {
        $santri = $this->santri();
        foreach (range(1, 22) as $number) NotificationLog::create(['calon_santri_id' => $santri->id, 'channel' => 'email', 'status' => 'success', 'message' => 'Catatan ' . $number]);
        $this->get(route('admin.show', ['id' => $santri->id, 'history_page' => 2]))->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertViewHas('notificationHistory', fn ($logs) => $logs->total() === 22 && $logs->count() === 10 && $logs->currentPage() === 2);
    }

    public function test_registry_eager_loads_contact_context_without_per_row_queries(): void
    {
        foreach (range(1, 22) as $number) $this->santri();
        $this->get(route('admin.dashboard', ['per_page' => 20]))->assertOk()->assertViewHas('pendaftar', function ($rows) {
            return $rows->count() === 20 && $rows->every(fn ($row) => $row->relationLoaded('periode') && $row->relationLoaded('gelombang') && $row->relationLoaded('latestNotificationLog'));
        });
    }

    public function test_partial_document_review_preserves_other_document_statuses(): void
    {
        $santri = $this->santri(['foto_akta_anak' => 'pendaftaran/akta.jpg', 'foto_kk' => 'pendaftaran/kk.jpg', 'dokumen_status' => ['foto_akta_anak' => 'valid', 'foto_kk' => 'menunggu_review']]);
        $this->post(route('admin.documents', $santri->id), ['dokumen_status' => ['foto_kk' => 'perlu_perbaikan']])->assertRedirect();
        $this->assertSame('valid', $santri->fresh()->dokumen_status['foto_akta_anak']);
        $this->assertSame('perlu_perbaikan', $santri->fresh()->dokumen_status['foto_kk']);
    }
}

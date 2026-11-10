<?php

namespace App\Support;

use App\Models\CalonSantri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AdminRegistrationQuery
{
    public const DOCUMENTS = ['foto_akta_anak', 'foto_kk', 'foto_ktp_ayah', 'foto_ktp_ibu', 'foto_pas_siswa'];

    public static function filters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:160',
            'status_pendaftaran' => ['nullable', Rule::in(['Pending', 'Diterima', 'Ditolak'])],
            'jenis_kelamin' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'periode_id' => 'nullable|integer|min:1|exists:periodes,id',
            'gelombang_id' => 'nullable|integer|min:1|exists:gelombangs,id',
            'dokumen' => ['nullable', Rule::in(['missing', 'present', 'review', 'revision', 'verified'])],
            'followup' => ['nullable', Rule::in(['need_contact', 'need_group', 'done'])],
            'notifikasi' => ['nullable', Rule::in(['failed', 'success', 'skipped', 'none'])],
            'dari' => 'nullable|date_format:Y-m-d',
            'sampai' => ['nullable', 'date_format:Y-m-d', $request->filled('dari') ? 'after_or_equal:dari' : 'sometimes'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'name_asc', 'name_desc', 'updated'])],
            'per_page' => ['nullable', Rule::in([20, 50, 100])],
            'page' => 'nullable|integer|min:1',
            'follow_page' => 'nullable|integer|min:1',
            'log_page' => 'nullable|integer|min:1',
            'notification_page' => 'nullable|integer|min:1',
        ]);
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $search = trim($filters['search'] ?? '');
        $phoneSearch = preg_replace('/[\s()+.\-]/', '', $search);
        if (strlen($phoneSearch) <= 15 && preg_match('/^(?:628|08|8)[0-9]{7,}$/', $phoneSearch)) $search = $phoneSearch;
        foreach (array_slice(preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY), 0, 8) as $term) {
            // Treat LIKE metacharacters as literal characters, not a request for all rows.
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
            $query->where(function (Builder $group) use ($like, $term) {
                foreach (['nomor_pendaftaran', 'nik', 'nama_lengkap', 'nama_ayah', 'nama_ibu', 'nama_wali', 'nik_ayah', 'nik_ibu', 'nik_wali', 'no_wa_ayah', 'no_wa_ibu', 'no_wa_wali', 'email_ayah', 'email_ibu', 'email_wali', 'nama_sekolah_asal', 'alamat_lengkap', 'kelurahan_desa', 'kecamatan', 'kabupaten_kota', 'propinsi'] as $column) {
                    $group->orWhereRaw("LOWER({$column}) LIKE LOWER(?) ESCAPE '!'", [$like]);
                }
                $phone = preg_replace('/[()+.\-]/', '', $term);
                if (preg_match('/^(?:\+?62|08|8)[0-9]{6,}$/', $phone)) {
                    $local = str_starts_with($phone, '628') ? '0' . substr($phone, 2) : (str_starts_with($phone, '8') ? '0' . $phone : $phone);
                    $international = str_starts_with($local, '08') ? '62' . substr($local, 1) : $phone;
                    foreach (['no_wa_ayah', 'no_wa_ibu', 'no_wa_wali'] as $column) $group->orWhereIn($column, [$local, $international]);
                }
            });
        }
        foreach (['status_pendaftaran', 'jenis_kelamin', 'periode_id', 'gelombang_id'] as $column) {
            if (!empty($filters[$column])) $query->where($column, $filters[$column]);
        }
        if (!empty($filters['dari'])) $query->where('created_at', '>=', \Carbon\Carbon::createFromFormat('!Y-m-d', $filters['dari'], 'Asia/Jakarta')->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'));
        if (!empty($filters['sampai'])) $query->where('created_at', '<', \Carbon\Carbon::createFromFormat('!Y-m-d', $filters['sampai'], 'Asia/Jakarta')->addDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'));
        if (!empty($filters['dokumen'])) self::documents($query, $filters['dokumen']);
        if (($filters['followup'] ?? '') === 'need_contact') self::notDone($query, 'followup_sudah_dihubungi');
        if (($filters['followup'] ?? '') === 'need_group') self::notDone($query, 'followup_sudah_masuk_grup');
        if (($filters['followup'] ?? '') === 'done') $query->where('followup_sudah_dihubungi', true)->where('followup_sudah_masuk_grup', true);
        if (($filters['notifikasi'] ?? '') === 'none') $query->whereDoesntHave('latestNotificationLog');
        elseif (!empty($filters['notifikasi'])) $query->whereHas('latestNotificationLog', fn (Builder $log) => $log->where('status', $filters['notifikasi']));
        return $query;
    }

    public static function order(Builder $query, string $sort = 'newest'): Builder
    {
        return match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'name_asc' => $query->orderBy('nama_lengkap')->orderBy('id'),
            'name_desc' => $query->orderByDesc('nama_lengkap')->orderBy('id'),
            'updated' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    public static function notDone(Builder $query, string $field): Builder
    {
        return $query->where(fn (Builder $group) => $group->where($field, false)->orWhereNull($field));
    }

    public static function documents(Builder $query, string $state): Builder
    {
        if (in_array($state, ['present', 'verified'], true)) {
            foreach (self::DOCUMENTS as $field) $query->whereNotNull($field)->where($field, '!=', '');
        }
        if ($state === 'verified') {
            foreach (self::DOCUMENTS as $field) $query->where('dokumen_status->' . $field, 'valid');
        } elseif ($state === 'missing') {
            $query->where(function (Builder $group) {
                foreach (self::DOCUMENTS as $field) $group->orWhereNull($field)->orWhere($field, '');
            });
        } elseif (in_array($state, ['review', 'revision'], true)) {
            $query->where(function (Builder $group) use ($state) {
                foreach (self::DOCUMENTS as $field) {
                    $group->orWhere(function (Builder $item) use ($field, $state) {
                        $item->whereNotNull($field)->where($field, '!=', '');
                        if ($state === 'revision') $item->where('dokumen_status->' . $field, 'perlu_perbaikan');
                        else $item->where(fn (Builder $status) => $status->whereNull('dokumen_status->' . $field)->orWhereIn('dokumen_status->' . $field, ['kosong', 'menunggu_review']));
                    });
                }
            });
        }
        return $query;
    }

    public static function attention(Builder $query): Builder
    {
        return $query->where(function (Builder $group) {
            $group->where('status_pendaftaran', 'Pending')
                ->orWhere(fn (Builder $q) => self::notDone($q, 'followup_sudah_dihubungi'))
                ->orWhere(fn (Builder $q) => self::notDone($q, 'followup_sudah_masuk_grup'))
                ->orWhere(fn (Builder $q) => self::documents($q, 'missing'))
                ->orWhere(fn (Builder $q) => self::documents($q, 'review'))
                ->orWhere(fn (Builder $q) => self::documents($q, 'revision'));
        });
    }
}

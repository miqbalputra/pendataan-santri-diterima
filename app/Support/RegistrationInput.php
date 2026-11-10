<?php

namespace App\Support;

use Illuminate\Http\Request;

final class RegistrationInput
{
    // Only form data may enter registration. Verification fields belong to the server.
    public const FIELDS = [
        'nama_lengkap',
        'jenis_kelamin',
        'nisn',
        'no_seri_ijazah',
        'no_seri_skhun',
        'no_ujian_nasional',
        'nik',
        'nama_sekolah_asal',
        'npsn_sekolah_asal',
        'alamat_sekolah_asal',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'berkebutuhan_khusus',
        'alamat_lengkap',
        'dusun',
        'rt_rw',
        'kelurahan_desa',
        'kecamatan',
        'kabupaten_kota',
        'propinsi',
        'kode_pos',
        'alat_transportasi',
        'jenis_tinggal',
        'no_telepon_rumah',
        'email',
        'hobi',
        'nama_ayah',
        'nik_ayah',
        'tempat_lahir_ayah',
        'tanggal_lahir_ayah',
        'berkebutuhan_khusus_ayah',
        'pekerjaan_ayah',
        'pendidikan_ayah',
        'no_wa_ayah',
        'penghasilan_ayah',
        'alamat_ayah',
        'rt_rw_ayah',
        'kelurahan_desa_ayah',
        'kecamatan_ayah',
        'status_tahsin_ayah',
        'pengajar_tahsin_ayah',
        'nama_ibu',
        'nik_ibu',
        'tempat_lahir_ibu',
        'tanggal_lahir_ibu',
        'berkebutuhan_khusus_ibu',
        'pekerjaan_ibu',
        'pendidikan_ibu',
        'no_wa_ibu',
        'penghasilan_ibu',
        'alamat_ibu',
        'rt_rw_ibu',
        'kelurahan_desa_ibu',
        'kecamatan_ibu',
        'status_tahsin_ibu',
        'pengajar_tahsin_ibu',
        'nama_wali',
        'tahun_lahir_wali',
        'berkebutuhan_khusus_wali',
        'pekerjaan_wali',
        'pendidikan_wali',
        'no_wa_wali',
        'penghasilan_wali',
        'nik_wali',
        'tempat_lahir_wali',
        'tanggal_lahir_wali',
        'alamat_wali',
        'rt_rw_wali',
        'kelurahan_desa_wali',
        'kecamatan_wali',
        'status_tahsin_wali',
        'pengajar_tahsin_wali',
        'email_ayah',
        'email_ibu',
        'email_wali',
        'tinggi_badan',
        'berat_badan',
        'jarak_ke_sekolah',
        'waktu_tempuh',
        'jumlah_saudara_kandung',
        'punya_saudara_di_sini',
        'siblings_data',
        'penandatangan_nama',
        'pernyataan_kebenaran_data',
    ];

    public static function normalize(Request $request): void
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $value = $request->input($field);
            if (!is_string($value)) continue;
            $value = trim(preg_replace('/[ \t]+/u', ' ', $value));
            if (str_starts_with($field, 'nik') || $field === 'rt_rw' || str_starts_with($field, 'rt_rw_')) {
                $value = preg_replace('/\s+/', '', $value);
            }
            if (str_starts_with($field, 'no_wa_')) {
                $value = preg_replace('/[\s()+.\-]/', '', $value);
                if (str_starts_with($value, '628')) $value = '0' . substr($value, 2);
                elseif (str_starts_with($value, '8')) $value = '0' . $value;
            }
            $values[$field] = $value === '' ? null : $value;
        }
        $request->merge($values);
    }

    public static function optionalRules(): array
    {
        $rules = array_fill_keys(self::FIELDS, 'nullable|string|max:255');
        $rules['alamat_lengkap'] = $rules['alamat_sekolah_asal'] = 'nullable|string|max:2000';
        $rules['punya_saudara_di_sini'] = 'sometimes|boolean';
        $rules['pernyataan_kebenaran_data'] = 'accepted';
        $rules['siblings_data'] = 'nullable|array|max:20';
        $rules['siblings_data.*'] = 'array';
        foreach (['ayah', 'ibu', 'wali'] as $role) {
            $rules['tanggal_lahir_' . $role] = 'nullable|date_format:Y-m-d|before:today';
            $rules['rt_rw_' . $role] = ['nullable', 'regex:/^\d{1,3}\/\d{1,3}$/'];
            $rules['alamat_' . $role] = 'nullable|string|max:2000';
            $rules['nik_' . $role] = 'nullable|digits:16';
            $rules['status_tahsin_' . $role] = 'nullable|in:Sudah,Belum';
            $rules['email_' . $role] = 'nullable|email|max:255';
        }
        $rules['email'] = 'nullable|email|max:255';
        $rules['no_wa_wali'] = ['nullable', 'string', 'max:25', 'regex:/^(08|628)[0-9]{8,13}$/'];
        $rules['nisn'] = 'nullable|digits:10';
        $rules['npsn_sekolah_asal'] = 'nullable|digits:8';
        $rules['tinggi_badan'] = 'nullable|numeric|gt:0|max:300';
        $rules['berat_badan'] = 'nullable|numeric|gt:0|max:500';
        $rules['jumlah_saudara_kandung'] = 'nullable|integer|min:0|max:100';
        $rules['tanggal_lahir_ayah'] = $rules['tanggal_lahir_ibu'] = 'nullable|date_format:Y-m-d|before:tanggal_lahir';
        return $rules;
    }
}

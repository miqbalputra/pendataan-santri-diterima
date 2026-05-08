<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalonSantri extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nomor_pendaftaran',
        'periode_id',
        'gelombang_id',
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
        'foto_ktp_ayah',
        'foto_ktp_ibu',
        'foto_akta_anak',
        'foto_kk',
        'foto_pas_siswa',
        'penandatangan_nama',
        'pernyataan_kebenaran_data',
        'tanda_tangan',
        'status_pendaftaran',
        'dokumen_status',
        'dokumen_catatan',
        'revisi_token',
        'revisi_diminta_pada',
        'revisi_selesai_pada',
        'followup_sudah_masuk_grup',
        'followup_sudah_dihubungi',
        'followup_catatan',
    ];
    
    protected $casts = [
        'siblings_data' => 'array',
        'is_ayah_tahsin' => 'boolean',
        'is_ibu_tahsin' => 'boolean',
        'punya_saudara_di_sini' => 'boolean',
        'pernyataan_kebenaran_data' => 'boolean',
        'dokumen_status' => 'array',
        'revisi_diminta_pada' => 'datetime',
        'revisi_selesai_pada' => 'datetime',
        'followup_sudah_masuk_grup' => 'boolean',
        'followup_sudah_dihubungi' => 'boolean',
    ];

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }

    public function gelombang()
    {
        return $this->belongsTo(Gelombang::class);
    }
}

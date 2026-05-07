<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Pendaftaran - {{ $santri->nama_lengkap }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        @page {
            margin: 1.5cm;
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 10px; 
            line-height: 1.4; 
            color: #000; 
            margin: 0; 
            padding: 0;
            background-color: #fff; 
        }
        .header { 
            text-align: center; 
            border-bottom: 1.5pt solid #000; 
            padding-bottom: 5px; 
            margin-bottom: 15px; 
        }
        .header h1 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .header p.sub-title { margin: 2px 0; font-size: 10pt; font-weight: bold; }
        .header p.address { margin: 2px 0; font-size: 8pt; color: #333; }
        
        .main-title { text-align: center; text-decoration: underline; font-size: 11pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
        .reg-number { text-align: center; font-size: 8pt; margin-bottom: 15px; font-family: monospace; }

        .section-title { 
            background: #f0f0f0; 
            color: #000; 
            padding: 4px 8px; 
            font-weight: bold; 
            text-transform: uppercase; 
            margin: 12px 0 6px 0; 
            border: 0.5pt solid #000;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; table-layout: fixed; }
        table td { padding: 3px 4px; vertical-align: top; word-wrap: break-word; }
        table td.label { width: 115px; color: #000; font-weight: normal; }
        table td.separator { width: 10px; text-align: center; }
        table td.value { font-weight: bold; border-bottom: 0.5pt solid #eee; }
        
        .grid-2 { display: table; width: 100%; border-spacing: 15px 0; margin-left: -7px; margin-right: -7px; }
        .col { display: table-cell; width: 50%; vertical-align: top; }

        .document-image {
            max-width: 100%;
            max-height: 160px;
            border: 0.5pt solid #000;
            margin-bottom: 5px;
        }

        .signature-section { margin-top: 25px; text-align: right; }
        .signature-box { display: inline-block; text-align: center; width: 200px; }
        .signature-space { height: 60px; margin: 5px 0; position: relative; }
        .ttd-img { max-height: 60px; max-width: 150px; }

        .footer-note { 
            margin-top: 30px; 
            font-size: 7pt; 
            color: #666; 
            text-align: center; 
            border-top: 0.5pt solid #ccc;
            padding-top: 8px;
        }

        .page-break { page-break-after: always; }
        
        /* Specific adjustments for smaller tables in grid */
        .col table td.label { width: 90px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>{{ $kop['baris_1'] }}</h1>
            <p class="sub-title">{{ $kop['baris_2'] }}</p>
            <p class="address">{{ $kop['baris_3'] }}</p>
        </div>

        <div class="main-title">Formulir Pendaftaran Peserta Didik Baru</div>
        <div class="reg-number">Nomor Registrasi: PSB-{{ str_pad($santri->id, 5, '0', STR_PAD_LEFT) }}</div>

        <!-- I. IDENTITAS -->
        <div class="section-title">I. Identitas Calon Peserta Didik</div>
        <div class="grid-2">
            <div class="col">
                <table>
                    <tr><td class="label">Nama Lengkap</td><td class="separator">:</td><td class="value">{{ $santri->nama_lengkap }}</td></tr>
                    <tr><td class="label">NIK</td><td class="separator">:</td><td class="value">{{ $santri->nik }}</td></tr>
                    <tr><td class="label">Jenis Kelamin</td><td class="separator">:</td><td class="value">{{ $santri->jenis_kelamin }}</td></tr>
                    <tr><td class="label">Tempat Lahir</td><td class="separator">:</td><td class="value">{{ $santri->tempat_lahir }}</td></tr>
                    <tr><td class="label">Tanggal Lahir</td><td class="separator">:</td><td class="value">{{ \Carbon\Carbon::parse($santri->tanggal_lahir)->translatedFormat('d F Y') }}</td></tr>
                    <tr><td class="label">Agama</td><td class="separator">:</td><td class="value">{{ $santri->agama ?: '-' }}</td></tr>
                    <tr><td class="label">Hobi</td><td class="separator">:</td><td class="value">{{ $santri->hobi ?: '-' }}</td></tr>
                </table>
            </div>
            <div class="col">
                <table>
                    <tr><td class="label">NISN</td><td class="separator">:</td><td class="value">{{ $santri->nisn ?: '-' }}</td></tr>
                    <tr><td class="label">No. Seri Ijazah</td><td class="separator">:</td><td class="value">{{ $santri->no_seri_ijazah ?: '-' }}</td></tr>
                    <tr><td class="label">No. Seri SKHUN</td><td class="separator">:</td><td class="value">{{ $santri->no_seri_skhun ?: '-' }}</td></tr>
                    <tr><td class="label">No. Ujian Nas.</td><td class="separator">:</td><td class="value">{{ $santri->no_ujian_nasional ?: '-' }}</td></tr>
                    <tr><td class="label">Sekolah Asal</td><td class="separator">:</td><td class="value">{{ $santri->nama_sekolah_asal }}</td></tr>
                    <tr><td class="label">NPSN Sek. Asal</td><td class="separator">:</td><td class="value">{{ $santri->npsn_sekolah_asal ?: '-' }}</td></tr>
                    <tr><td class="label">Kebutuhan Khusus</td><td class="separator">:</td><td class="value">{{ $santri->berkebutuhan_khusus ?: 'Tidak ada' }}</td></tr>
                </table>
            </div>
        </div>
        <table>
            <tr><td class="label" style="width:115px">Alamat Sekolah Asal</td><td class="separator">:</td><td class="value">{{ $santri->alamat_sekolah_asal ?: '-' }}</td></tr>
        </table>

        <!-- II. ALAMAT -->
        <div class="section-title">II. Alamat Domisili</div>
        <table>
            <tr><td class="label" style="width:115px">Alamat Lengkap</td><td class="separator">:</td><td class="value">{{ $santri->alamat_lengkap }}</td></tr>
        </table>
        <div class="grid-2">
            <div class="col">
                <table>
                    <tr><td class="label">Dusun / Desa</td><td class="separator">:</td><td class="value">{{ $santri->dusun ?: '-' }} / {{ $santri->kelurahan_desa ?: '-' }}</td></tr>
                    <tr><td class="label">RT / RW</td><td class="separator">:</td><td class="value">{{ $santri->rt_rw ?: '-' }}</td></tr>
                    <tr><td class="label">Kecamatan</td><td class="separator">:</td><td class="value">{{ $santri->kecamatan ?: '-' }}</td></tr>
                    <tr><td class="label">Kabupaten/Kota</td><td class="separator">:</td><td class="value">{{ $santri->kabupaten_kota ?: '-' }}</td></tr>
                </table>
            </div>
            <div class="col">
                <table>
                    <tr><td class="label">Provinsi</td><td class="separator">:</td><td class="value">{{ $santri->propinsi ?: '-' }}</td></tr>
                    <tr><td class="label">Kode Pos</td><td class="separator">:</td><td class="value">{{ $santri->kode_pos ?: '-' }}</td></tr>
                    <tr><td class="label">Jenis Tinggal</td><td class="separator">:</td><td class="value">{{ $santri->jenis_tinggal ?: '-' }}</td></tr>
                    <tr><td class="label">Alat Transportasi</td><td class="separator">:</td><td class="value">{{ $santri->alat_transportasi ?: '-' }}</td></tr>
                </table>
            </div>
        </div>

        <!-- III. DATA ORANG TUA -->
        <div class="section-title">III. Data Orang Tua Kandung</div>
        <div class="grid-2">
            <div class="col">
                <p style="font-weight:bold; text-decoration:underline; margin-bottom:4px;">DATA AYAH:</p>
                <table>
                    <tr><td class="label">Nama Ayah</td><td class="separator">:</td><td class="value">{{ $santri->nama_ayah }}</td></tr>
                    <tr><td class="label">NIK Ayah</td><td class="separator">:</td><td class="value">{{ $santri->nik_ayah ?: '-' }}</td></tr>
                    <tr><td class="label">Tempat/Tgl Lahir</td><td class="separator">:</td><td class="value">{{ $santri->tempat_lahir_ayah ?: '-' }}, {{ $santri->tanggal_lahir_ayah ? \Carbon\Carbon::parse($santri->tanggal_lahir_ayah)->translatedFormat('d-m-Y') : '-' }}</td></tr>
                    <tr><td class="label">Pekerjaan</td><td class="separator">:</td><td class="value">{{ $santri->pekerjaan_ayah ?: '-' }}</td></tr>
                    <tr><td class="label">Pendidikan</td><td class="separator">:</td><td class="value">{{ $santri->pendidikan_ayah ?: '-' }}</td></tr>
                    <tr><td class="label">Penghasilan</td><td class="separator">:</td><td class="value">{{ $santri->penghasilan_ayah ?: '-' }}</td></tr>
                    <tr><td class="label">Nomor WA</td><td class="separator">:</td><td class="value">{{ $santri->no_wa_ayah ?: '-' }}</td></tr>
                    <tr><td class="label">Email</td><td class="separator">:</td><td class="value">{{ $santri->email_ayah ?: '-' }}</td></tr>
                    <tr><td class="label">Status Tahsin</td><td class="separator">:</td><td class="value">{{ $santri->status_tahsin_ayah }} (Ustadz: {{ $santri->pengajar_tahsin_ayah ?: '-' }})</td></tr>
                </table>
            </div>
            <div class="col">
                <p style="font-weight:bold; text-decoration:underline; margin-bottom:4px;">DATA IBU:</p>
                <table>
                    <tr><td class="label">Nama Ibu</td><td class="separator">:</td><td class="value">{{ $santri->nama_ibu }}</td></tr>
                    <tr><td class="label">NIK Ibu</td><td class="separator">:</td><td class="value">{{ $santri->nik_ibu ?: '-' }}</td></tr>
                    <tr><td class="label">Tempat/Tgl Lahir</td><td class="separator">:</td><td class="value">{{ $santri->tempat_lahir_ibu ?: '-' }}, {{ $santri->tanggal_lahir_ibu ? \Carbon\Carbon::parse($santri->tanggal_lahir_ibu)->translatedFormat('d-m-Y') : '-' }}</td></tr>
                    <tr><td class="label">Pekerjaan</td><td class="separator">:</td><td class="value">{{ $santri->pekerjaan_ibu ?: '-' }}</td></tr>
                    <tr><td class="label">Pendidikan</td><td class="separator">:</td><td class="value">{{ $santri->pendidikan_ibu ?: '-' }}</td></tr>
                    <tr><td class="label">Penghasilan</td><td class="separator">:</td><td class="value">{{ $santri->penghasilan_ibu ?: '-' }}</td></tr>
                    <tr><td class="label">Nomor WA</td><td class="separator">:</td><td class="value">{{ $santri->no_wa_ibu ?: '-' }}</td></tr>
                    <tr><td class="label">Email</td><td class="separator">:</td><td class="value">{{ $santri->email_ibu ?: '-' }}</td></tr>
                    <tr><td class="label">Status Tahsin</td><td class="separator">:</td><td class="value">{{ $santri->status_tahsin_ibu }} (Ustadzah: {{ $santri->pengajar_tahsin_ibu ?: '-' }})</td></tr>
                </table>
            </div>
        </div>

        @if($santri->nama_wali)
        <div class="section-title">IV. Data Wali</div>
        <div class="grid-2">
            <div class="col">
                <table>
                    <tr><td class="label">Nama Wali</td><td class="separator">:</td><td class="value">{{ $santri->nama_wali }}</td></tr>
                    <tr><td class="label">NIK Wali</td><td class="separator">:</td><td class="value">{{ $santri->nik_wali ?: '-' }}</td></tr>
                    <tr><td class="label">Tempat/Tgl Lahir</td><td class="separator">:</td><td class="value">{{ $santri->tempat_lahir_wali ?: '-' }}, {{ $santri->tanggal_lahir_wali ? \Carbon\Carbon::parse($santri->tanggal_lahir_wali)->translatedFormat('d-m-Y') : '-' }}</td></tr>
                </table>
            </div>
            <div class="col">
                <table>
                    <tr><td class="label">Pekerjaan</td><td class="separator">:</td><td class="value">{{ $santri->pekerjaan_wali ?: '-' }}</td></tr>
                    <tr><td class="label">Pendidikan</td><td class="separator">:</td><td class="value">{{ $santri->pendidikan_wali ?: '-' }}</td></tr>
                    <tr><td class="label">Nomor WA</td><td class="separator">:</td><td class="value">{{ $santri->no_wa_wali ?: '-' }}</td></tr>
                </table>
            </div>
        </div>
        @endif

        <div class="section-title">V. Data Periodik & Saudara</div>
        <div class="grid-2">
            <div class="col">
                <table>
                    <tr><td class="label">Tinggi / Berat</td><td class="separator">:</td><td class="value">{{ $santri->tinggi_badan }} cm / {{ $santri->berat_badan }} kg</td></tr>
                    <tr><td class="label">Jarak ke Sekolah</td><td class="separator">:</td><td class="value">{{ $santri->jarak_ke_sekolah ?: '-' }}</td></tr>
                </table>
            </div>
            <div class="col">
                <table>
                    <tr><td class="label">Waktu Tempuh</td><td class="separator">:</td><td class="value">{{ $santri->waktu_tempuh ?: '-' }}</td></tr>
                    <tr><td class="label">Jumlah Saudara</td><td class="separator">:</td><td class="value">{{ $santri->jumlah_saudara_kandung ?: '0' }} orang</td></tr>
                </table>
            </div>
        </div>

        <!-- SIGNATURE -->
        <div class="signature-section">
            <div class="signature-box">
                <p>{{ $santri->kabupaten_kota ?: 'Kota' }}, {{ date('d F Y') }}</p>
                <p>Orang Tua / Wali,</p>
                <div class="signature-space">
                    @if($santri->tanda_tangan)
                        <img src="{{ route('pendaftaran.berkas', ['id' => $santri->id, 'field' => 'tanda_tangan']) }}" class="ttd-img">
                    @else
                        <div style="color:#666; padding-top:20px; font-style:italic; font-size:8pt; border: 1px dashed #ccc; width: 140px; margin: auto;">Tanda tangan tidak tersedia</div>
                    @endif
                </div>
                <p><strong>( {{ $santri->penandatangan_nama ?: $santri->nama_ayah }} )</strong></p>
            </div>
        </div>

        <div class="footer-note">
            Dokumen ini dihasilkan secara otomatis oleh Sistem Pendaftaran Peserta Didik Baru (SPSB) pada {{ date('d-m-Y H:i:s') }}.<br>
            Seluruh data yang tertera adalah benar dan sesuai dengan dokumen yang diunggah oleh pendaftar.
        </div>
    </div>

    <!-- PAGE 2: DOKUMEN -->
    <div class="page-break"></div>
    <div class="container">
        <div class="section-title">Lampiran Dokumen Digital</div>
        
        <div style="display:table; width:100%; text-align:center; margin-bottom:20px; border-bottom: 0.5pt solid #000; padding-bottom: 15px;">
            <div style="display:table-cell; width:20%">
                @if($santri->foto_pas_siswa)
                    <img src="{{ route('pendaftaran.berkas', ['id' => $santri->id, 'field' => 'foto_pas_siswa']) }}" style="width:3cm; height:4cm; border:0.5pt solid #000">
                    <p style="font-size:7pt; margin-top:5px;">Pas Foto 3x4</p>
                @else
                    <div style="width:3cm; height:4cm; border:0.5pt dashed #ccc; margin:auto"></div>
                @endif
            </div>
            <div style="display:table-cell; width:80%; text-align:left; vertical-align:middle; padding-left:30px">
                <h2 style="font-size:12pt; margin:0 0 5px 0;">VERIFIKASI BERKAS DIGITAL</h2>
                <p style="font-size:9pt; margin:0; color:#333;">Dokumen di bawah ini merupakan salinan digital dari berkas asli yang diunggah oleh pendaftar sebagai syarat validasi data di Dapodik.</p>
            </div>
        </div>

        <div style="display:table; width:100%">
            <!-- Baris 1: KTP Bapak & Ibu (Berdampingan) -->
            <div style="display:table-row">
                <div style="display:table-cell; width:50%; padding:10px; text-align:center">
                    <p style="font-weight:bold; margin-bottom:5px; font-size:9pt;">KTP AYAH</p>
                    @if($santri->foto_ktp_ayah)
                        <img src="{{ route('pendaftaran.berkas', ['id' => $santri->id, 'field' => 'foto_ktp_ayah']) }}" class="document-image">
                    @else
                        <div style="height:100px; border:0.5pt dashed #ccc; padding-top:40px; font-size:8pt; color:#666;">Berkas tidak tersedia</div>
                    @endif
                </div>
                <div style="display:table-cell; width:50%; padding:10px; text-align:center">
                    <p style="font-weight:bold; margin-bottom:5px; font-size:9pt;">KTP IBU</p>
                    @if($santri->foto_ktp_ibu)
                        <img src="{{ route('pendaftaran.berkas', ['id' => $santri->id, 'field' => 'foto_ktp_ibu']) }}" class="document-image">
                    @else
                        <div style="height:100px; border:0.5pt dashed #ccc; padding-top:40px; font-size:8pt; color:#666;">Berkas tidak tersedia</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Baris 2: Kartu Keluarga (Full Width) -->
        <div style="margin-top:20px; text-align:center; border-top: 0.5pt dashed #ccc; padding-top:15px;">
            <p style="font-weight:bold; margin-bottom:5px; font-size:9pt;">KARTU KELUARGA (KK)</p>
            @if($santri->foto_kk)
                <img src="{{ route('pendaftaran.berkas', ['id' => $santri->id, 'field' => 'foto_kk']) }}" style="width:100%; max-height:450px; border:0.5pt solid #000">
            @else
                <div style="height:100px; border:0.5pt dashed #ccc; padding-top:40px; font-size:8pt; color:#666;">Berkas KK tidak tersedia</div>
            @endif
        </div>

        <div class="page-break"></div>
        <div class="container">
            <div class="section-title">Lampiran Dokumen Digital (Lanjutan)</div>
            <!-- Baris 3: Akta Kelahiran (Full Width) -->
            <div style="margin-top:10px; text-align:center;">
                <p style="font-weight:bold; margin-bottom:5px; font-size:9pt;">AKTA KELAHIRAN ANAK</p>
                @if($santri->foto_akta_anak)
                    <img src="{{ route('pendaftaran.berkas', ['id' => $santri->id, 'field' => 'foto_akta_anak']) }}" style="width:100%; max-height:750px; border:0.5pt solid #000">
                @else
                    <div style="height:150px; border:0.5pt dashed #ccc; padding-top:70px; font-size:8pt; color:#666;">Berkas Akta tidak tersedia</div>
                @endif
            </div>

            <div class="footer-note" style="margin-top:30px">
                Lembar Lampiran Berkas Digital - Halaman 3
            </div>
        </div>
</body>
</html>


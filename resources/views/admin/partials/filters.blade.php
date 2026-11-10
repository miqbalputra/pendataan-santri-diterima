<form method="GET" action="{{ route('admin.dashboard') }}" class="space-y-4" data-admin-filters>
    <input type="hidden" name="tab" value="{{ $filterTab ?? 'dashboard' }}">
    <div class="flex flex-col sm:flex-row gap-3">
        <label class="block flex-1 min-w-0">
            <span class="block text-sm font-semibold text-slate-700 mb-2">Cari peserta atau keluarga</span>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="160" class="form-input bg-white" placeholder="Nama, NIK, nomor pendaftaran, WhatsApp, sekolah, atau alamat…">
        </label>
        <button class="self-end w-full sm:w-auto rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white px-6 py-3 font-bold text-sm">Cari & terapkan</button>
        <a href="{{ route('admin.dashboard', ['tab' => $filterTab ?? 'dashboard']) }}" class="self-end w-full sm:w-auto text-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600">Reset filter</a>
    </div>
    <details @if(collect($filters)->except(['search', 'sort', 'per_page', 'page', 'follow_page', 'log_page', 'notification_page'])->filter(fn ($value) => filled($value))->isNotEmpty()) open @endif class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <summary class="cursor-pointer font-semibold text-sm text-slate-700">Filter lanjutan <span class="font-normal text-slate-500">— periode, status, dokumen, dan tindak lanjut</span></summary>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-4">
            @foreach([
                'status_pendaftaran' => ['Status verifikasi data', ['Pending' => 'Menunggu verifikasi', 'Diterima' => 'Sudah diverifikasi', 'Ditolak' => 'Tidak dilanjutkan']],
                'jenis_kelamin' => ['Jenis kelamin', ['Laki-laki' => 'Putra / Ikhwan', 'Perempuan' => 'Putri / Akhwat']],
                'dokumen' => ['Kondisi lampiran', ['missing' => 'Ada lampiran belum diunggah', 'present' => 'Lima lampiran tersedia', 'review' => 'Lampiran menunggu pemeriksaan', 'revision' => 'Ada dokumen perlu perbaikan', 'verified' => 'Lima dokumen dinyatakan valid']],
                'followup' => ['Tindak lanjut', ['need_contact' => 'Belum dihubungi', 'need_group' => 'Belum dikonfirmasi masuk grup', 'done' => 'Sudah dihubungi & masuk grup']],
                'notifikasi' => ['Webhook notifikasi terakhir', ['failed' => 'Gagal', 'success' => 'Diterima layanan', 'skipped' => 'Dilewati', 'none' => 'Belum ada catatan']],
            ] as $name => [$label, $options])
                <label class="block text-sm text-slate-600">{{ $label }}
                    <select name="{{ $name }}" class="form-input mt-2 bg-white"><option value="">Semua</option>
                        @foreach($options as $value => $text)<option value="{{ $value }}" @selected(($filters[$name] ?? '') === $value)>{{ $text }}</option>@endforeach
                    </select>
                </label>
            @endforeach
            <label class="block text-sm text-slate-600">Periode
                <select name="periode_id" class="form-input mt-2 bg-white"><option value="">Semua periode</option>
                    @foreach($periodes as $periode)<option value="{{ $periode->id }}" @selected(($filters['periode_id'] ?? '') == $periode->id)>{{ $periode->nama_periode }}{{ $periode->is_active ? ' (aktif)' : '' }}</option>@endforeach
                </select>
            </label>
            <label class="block text-sm text-slate-600">Gelombang
                <select name="gelombang_id" class="form-input mt-2 bg-white"><option value="">Semua gelombang</option>
                    @foreach($gelombangs as $gelombang)<option value="{{ $gelombang->id }}" @selected(($filters['gelombang_id'] ?? '') == $gelombang->id)>{{ $gelombang->nama_gelombang }}</option>@endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-2">
                <label class="block text-sm text-slate-600">Masuk sejak<input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}" class="form-input mt-2 bg-white"></label>
                <label class="block text-sm text-slate-600">Sampai tanggal<input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="form-input mt-2 bg-white"></label>
            </div>
        </div>
    </details>
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <label class="flex items-center gap-2 text-slate-600">Urutan
            <select name="sort" class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                @foreach(['newest' => 'Data terbaru', 'oldest' => 'Data terlama', 'name_asc' => 'Nama A–Z', 'name_desc' => 'Nama Z–A', 'updated' => 'Terakhir diperbarui'] as $value => $text)<option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $text }}</option>@endforeach
            </select>
        </label>
        <label class="flex items-center gap-2 text-slate-600">Baris
            <select name="per_page" class="rounded-lg border border-slate-200 bg-white px-3 py-2">@foreach([20, 50, 100] as $size)<option value="{{ $size }}" @selected(($filters['per_page'] ?? 20) == $size)>{{ $size }}</option>@endforeach</select>
        </label>
        <span class="text-xs text-slate-500">Tekan Enter atau “Cari & terapkan” untuk memperbarui hasil.</span>
    </div>
</form>

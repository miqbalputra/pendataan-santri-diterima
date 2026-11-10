@php
    $exportFilters = collect($filters)->except(['page', 'follow_page', 'log_page', 'notification_page', 'per_page'])->filter(fn ($value) => filled($value))->all();
    $completion = $stats['total'] ? round($stats['diterima'] / $stats['total'] * 100) : 0;
@endphp
<section class="mb-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Pusat operasional</p><h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Pendataan peserta didik</h2><p class="text-sm text-slate-500 mt-2">Temukan data, periksa lampiran, dan pantau pekerjaan panitia.</p></div>
    <div class="flex gap-2 text-sm"><a href="{{ url('/pendaftaran') }}" target="_blank" rel="noopener" class="rounded-xl bg-white border border-slate-200 px-4 py-3 font-semibold">Buka formulir ↗</a><a href="{{ route('admin.dashboard', request()->query()) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 font-semibold">Muat ulang</a></div>
</section>
<div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500 mb-3"><span>Ringkasan seluruh data aktif, termasuk di luar filter daftar.</span><span>Snapshot {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span></div>
<section aria-label="Ringkasan pendataan" class="grid grid-cols-2 xl:grid-cols-6 gap-3 mb-6">
    @foreach([
        ['Seluruh peserta', $stats['total'], [], 'text-slate-900'],
        ['Menunggu verifikasi', $stats['pending'], ['status_pendaftaran' => 'Pending'], 'text-amber-700'],
        ['Sudah diverifikasi', $stats['diterima'], ['status_pendaftaran' => 'Diterima'], 'text-emerald-700'],
        ['Tidak dilanjutkan', $stats['ditolak'], ['status_pendaftaran' => 'Ditolak'], 'text-rose-700'],
        ['Lampiran belum lengkap', $stats['missing'], ['dokumen' => 'missing'], 'text-orange-700'],
        ['Belum dihubungi', $stats['need_contact'], ['followup' => 'need_contact'], 'text-indigo-700'],
    ] as [$label, $value, $params, $color])
        <a href="{{ route('admin.dashboard', $params) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-emerald-400 focus-visible:outline-2 focus-visible:outline-emerald-700 transition"><span class="block text-xs font-semibold text-slate-500 leading-5">{{ $label }}</span><strong class="block text-3xl font-extrabold mt-2 {{ $color }}">{{ number_format($value, 0, ',', '.') }}</strong><span class="text-xs text-slate-400 mt-1 block">Lihat daftar →</span></a>
    @endforeach
</section>
<section class="grid lg:grid-cols-3 gap-4 mb-7" aria-label="Prioritas pekerjaan">
    <div class="rounded-2xl bg-slate-900 text-white p-5"><h3 class="font-bold">Progres verifikasi data</h3><p class="text-3xl font-extrabold mt-3">{{ $completion }}<span class="text-lg text-slate-400">%</span></p><div class="h-2 bg-white/15 rounded-full mt-3 overflow-hidden" role="progressbar" aria-label="Data sudah diverifikasi" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100"><div class="bg-emerald-400 h-full" style="width:{{ $completion }}%"></div></div><p class="text-xs text-slate-300 mt-3">{{ $stats['diterima'] }} dari {{ $stats['total'] }} peserta berstatus sudah diverifikasi. {{ $stats['verified_docs'] }} peserta memiliki lima dokumen yang dinyatakan valid.</p></div>
    <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5"><h3 class="font-bold text-slate-800 mb-3">Perlu perhatian panitia</h3><div class="grid sm:grid-cols-3 gap-3">
        @foreach([
            ['Dokumen perlu perbaikan', $stats['revision'], ['dokumen' => 'revision']],
            ['Belum dikonfirmasi masuk grup', $stats['need_group'], ['followup' => 'need_group']],
            ['Webhook terakhir gagal', $stats['notification_failed'], ['notifikasi' => 'failed']],
        ] as [$label, $value, $params])<a class="rounded-xl bg-slate-50 border border-slate-100 p-3 hover:bg-emerald-50 text-sm" href="{{ route('admin.dashboard', $params) }}"><strong class="text-xl block text-slate-900">{{ $value }}</strong><span class="block text-slate-600 mt-1">{{ $label }} →</span></a>@endforeach
    </div><p class="text-xs text-slate-500 mt-3">Lampiran tersedia belum berarti valid. Link grup dibuka belum berarti sudah bergabung. Status webhook menunjukkan respons layanan pengiriman.</p></div>
</section>
<section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" aria-labelledby="registry-title">
    <div class="p-5 sm:p-6 border-b border-slate-100"><div class="flex flex-wrap justify-between gap-3 mb-5"><div><h3 id="registry-title" class="text-xl font-bold">Daftar peserta didik</h3><p class="text-sm text-slate-500 mt-1">Pencarian mencakup identitas, keluarga, sekolah, dan alamat.</p></div><details class="relative"><summary class="cursor-pointer border border-slate-200 rounded-xl px-4 py-2.5 font-semibold text-sm">Unduh hasil pencarian</summary><div class="absolute right-0 z-20 bg-white border border-slate-200 shadow-lg rounded-xl p-2 min-w-52 mt-2">@foreach(['excel' => 'Excel lengkap', 'csv' => 'CSV lengkap', 'pdf' => 'Tampilan cetak'] as $format => $label)<a class="block px-3 py-2 hover:bg-slate-50 rounded-lg text-sm" href="{{ route('admin.export', $exportFilters + ['format' => $format]) }}">{{ $label }}</a>@endforeach</div></details></div>
        @include('admin.partials.filters')
    </div>
    <div class="px-5 sm:px-6 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap justify-between gap-2 text-sm" role="status"><p><strong>{{ number_format($pendaftar->total(), 0, ',', '.') }}</strong> hasil ditemukan dari {{ $stats['total'] }} data aktif <span class="text-slate-500">• {{ (int) $filteredCounts->pending }} menunggu • {{ (int) $filteredCounts->diterima }} terverifikasi</span></p><span class="text-slate-500">Baris {{ $pendaftar->firstItem() ?? 0 }}–{{ $pendaftar->lastItem() ?? 0 }}</span></div>
    @if($pendaftar->isEmpty())
        <div class="p-10 sm:p-16 text-center"><h4 class="text-lg font-bold text-slate-800">Belum ada data yang sesuai</h4><p class="text-slate-500 text-sm mt-2">Coba kata kunci lebih singkat, periksa rentang tanggal, atau reset filter.</p><a href="{{ route('admin.dashboard') }}" class="inline-block mt-5 text-emerald-700 font-bold text-sm underline">Tampilkan semua data</a></div>
    @else
    <div class="overflow-x-auto hidden lg:block" tabindex="0" aria-label="Tabel peserta didik, geser untuk melihat seluruh kolom">
        <table class="w-full text-left text-sm min-w-[1180px]"><caption class="sr-only">Hasil pencarian peserta didik beserta kontak, dokumen, dan tindak lanjut</caption><thead class="bg-slate-50 text-xs text-slate-500 border-b border-slate-200"><tr>@foreach(['Peserta didik', 'Orang tua & kontak', 'Periode & gelombang', 'Lampiran', 'Status & tindak lanjut', 'Aksi'] as $heading)<th scope="col" class="px-5 py-4 font-bold">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">
        @foreach($pendaftar as $p)
            @php($docs = $p->documentSummary())
            <tr class="hover:bg-slate-50/70 align-top">
                <td class="px-5 py-5 min-w-64"><a href="{{ route('admin.show', ['id' => $p->id, 'back' => http_build_query(request()->query())]) }}" class="font-bold text-slate-900 hover:text-emerald-700">{{ $p->nama_lengkap }}</a><p class="text-xs font-mono text-emerald-700 mt-1">{{ $p->nomor_pendaftaran ?: 'Belum bernomor' }}</p><p class="text-xs text-slate-500 mt-2">NIK {{ $p->nik ?: 'Belum diisi' }}</p><p class="text-xs text-slate-500 mt-1">{{ $p->jenis_kelamin === 'Laki-laki' ? 'Putra' : ($p->jenis_kelamin === 'Perempuan' ? 'Putri' : 'Belum diisi') }} • {{ $p->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</p></td>
                <td class="px-5 py-5 min-w-56">@foreach(['ayah' => 'Bapak', 'ibu' => 'Ibu'] as $role => $label)<div class="{{ $loop->last ? 'mt-3' : '' }}"><p class="text-xs text-slate-500">{{ $label }}: <span class="text-slate-700 font-semibold">{{ $p->{'nama_' . $role} ?: 'Belum diisi' }}</span></p>@if($p->{'no_wa_' . $role})<a class="text-xs font-semibold text-emerald-700 hover:underline" href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $p->{'no_wa_' . $role})) }}" target="_blank" rel="noopener">{{ $p->{'no_wa_' . $role} }} ↗</a>@else<span class="text-xs text-slate-400">Kontak belum diisi</span>@endif</div>@endforeach</td>
                <td class="px-5 py-5 min-w-40"><p class="font-semibold text-slate-700">{{ $p->periode?->nama_periode ?? 'Belum ditetapkan' }}</p><p class="text-xs text-slate-500 mt-1">{{ $p->gelombang?->nama_gelombang ?? 'Tanpa gelombang' }}</p><p class="text-xs text-slate-500 mt-3 max-w-44 whitespace-normal">{{ $p->kabupaten_kota ?: 'Kota belum diisi' }}</p></td>
                <td class="px-5 py-5 min-w-44"><p class="font-bold {{ $docs['missing'] ? 'text-orange-700' : 'text-slate-700' }}">{{ $docs['present'] }}/5 tersedia</p><p class="text-xs text-emerald-700 mt-1">{{ $docs['valid'] }}/5 dinyatakan valid</p>@if($docs['revision'])<p class="text-xs text-rose-700 mt-1">{{ $docs['revision'] }} perlu perbaikan</p>@endif @if($docs['review'])<p class="text-xs text-amber-700 mt-1">{{ $docs['review'] }} menunggu pemeriksaan</p>@endif</td>
                <td class="px-5 py-5 min-w-56">@include('admin.partials.registration-status', ['santri' => $p])</td>
                <td class="px-5 py-5"><a href="{{ route('admin.show', ['id' => $p->id, 'back' => http_build_query(request()->query())]) }}" class="inline-block rounded-lg bg-emerald-700 text-white px-4 py-2 font-bold text-xs">Lihat detail</a><a href="{{ route('admin.edit', $p->id) }}" class="block text-xs font-semibold text-slate-500 hover:text-emerald-700 mt-3">Edit data</a></td>
            </tr>
        @endforeach
        </tbody></table>
    </div>
    <div class="lg:hidden divide-y divide-slate-100">@foreach($pendaftar as $p) @php($docs = $p->documentSummary())<article class="p-5"><div class="flex justify-between gap-3"><div class="min-w-0"><h4 class="font-bold break-words">{{ $p->nama_lengkap }}</h4><p class="text-xs font-mono text-emerald-700 mt-1 break-all">{{ $p->nomor_pendaftaran ?: 'Belum bernomor' }}</p><p class="text-xs text-slate-500 mt-2">NIK {{ $p->nik }}</p></div><span class="text-xs text-slate-500 shrink-0">{{ $docs['present'] }}/5 lampiran</span></div><div class="mt-4">@include('admin.partials.registration-status', ['santri' => $p])</div><div class="mt-4 flex gap-2"><a href="{{ route('admin.show', ['id' => $p->id, 'back' => http_build_query(request()->query())]) }}" class="flex-1 text-center bg-emerald-700 text-white rounded-lg px-4 py-3 text-sm font-bold">Lihat data lengkap</a><a href="{{ route('admin.edit', $p->id) }}" class="rounded-lg px-4 py-3 text-sm border border-slate-200">Edit</a></div></article>@endforeach</div>
    @endif
    <div class="p-5 border-t border-slate-100">{{ $pendaftar->links('admin.partials.pagination') }}</div>
</section>

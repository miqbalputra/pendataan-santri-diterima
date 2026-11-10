@php
    $allFields = collect(\App\Support\RegistrationInput::FIELDS);
    $family = collect(['ayah', 'ibu', 'wali'])->mapWithKeys(fn ($role) => [$role => $allFields->filter(fn ($field) => str_ends_with($field, '_' . $role))->values()->all()]);
    $periodic = ['tinggi_badan', 'berat_badan', 'jarak_ke_sekolah', 'waktu_tempuh', 'jumlah_saudara_kandung', 'punya_saudara_di_sini', 'siblings_data'];
    $statement = ['penandatangan_nama', 'pernyataan_kebenaran_data'];
    $student = $allFields->diff(array_merge($family->flatten()->all(), $periodic, $statement))->values()->all();
    $sections = ['Identitas, sekolah & alamat anak' => $student, 'Data Bapak' => $family['ayah'], 'Data Ibu' => $family['ibu'], 'Data Wali' => $family['wali'], 'Data periodik & saudara' => $periodic, 'Pernyataan data' => $statement];
    $docSummary = $santri->documentSummary();
@endphp
<section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6" aria-label="Ringkasan administrasi">
    <h2 class="text-lg font-bold">Ringkasan administrasi</h2>
    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mt-4">
        @foreach(['Nomor pendataan' => $santri->nomor_pendaftaran, 'Periode' => $santri->periode?->nama_periode, 'Gelombang' => $santri->gelombang?->nama_gelombang, 'Data masuk' => $santri->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'), 'Terakhir diperbarui' => $santri->updated_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'), 'Lampiran' => $docSummary['present'] . '/5 tersedia · ' . $docSummary['valid'] . '/5 valid'] as $label => $value)
            <div class="min-w-0"><dt class="text-xs text-slate-500 mb-1">{{ $label }}</dt><dd class="font-semibold break-words">{{ $value ?: 'Belum ditetapkan' }}</dd></div>
        @endforeach
    </dl>
</section>
<section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5"><div><h2 class="text-lg font-bold">Seluruh isian formulir</h2><p class="text-xs text-slate-500 mt-1">Kolom kosong ditampilkan sebagai “Belum diisi”.</p></div><label class="block text-xs text-slate-500">Temukan kolom atau nilai<input type="search" id="profile-search" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Contoh: NIK Ibu, alamat, tahsin…"></label></div>
    <p id="profile-search-status" class="text-xs text-slate-500 mb-4" role="status" aria-live="polite"></p>
    <div class="space-y-7">
        @foreach($sections as $title => $fields)
            <section data-profile-section><h3 class="font-bold text-emerald-900 border-b border-slate-100 pb-3 mb-4">{{ $title }}</h3><dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4">
                @foreach($fields as $field)
                    @php
                        $value = $santri->{$field};
                        if (is_bool($value)) $display = $value ? 'Ya' : 'Tidak';
                        elseif (is_array($value)) $display = empty($value) ? 'Belum diisi' : 'Data tersedia';
                        else $display = filled($value) ? (string) $value : 'Belum diisi';
                    @endphp
                    <div data-profile-field class="min-w-0"><dt class="text-xs text-slate-500 mb-1">{{ $fieldLabels[$field] ?? \Illuminate\Support\Str::headline($field) }}</dt><dd class="text-sm font-semibold whitespace-pre-line break-words {{ $display === 'Belum diisi' ? 'text-slate-400 font-normal' : 'text-slate-800' }}">@if(is_array($value) && count($value))<ul class="space-y-2">@foreach($value as $key => $entry)<li class="rounded-lg bg-slate-50 p-3"><span class="text-xs text-slate-500">{{ is_numeric($key) ? 'Data ' . $loop->iteration : \Illuminate\Support\Str::headline($key) }}</span>@if(is_array($entry))@foreach($entry as $name => $text)<p><span class="font-normal">{{ \Illuminate\Support\Str::headline((string) $name) }}:</span> {{ is_array($text) ? collect($text)->flatten()->filter(fn ($item) => is_scalar($item))->implode(', ') : (is_bool($text) ? ($text ? 'Ya' : 'Tidak') : $text) }}</p>@endforeach @else<p>{{ is_bool($entry) ? ($entry ? 'Ya' : 'Tidak') : $entry }}</p>@endif</li>@endforeach</ul>@else{{ $display }}@endif</dd></div>
                @endforeach
            </dl></section>
        @endforeach
    </div>
</section>
<script>
    document.getElementById('profile-search').addEventListener('input', function () {
        const keyword = this.value.toLocaleLowerCase('id').trim();
        let matches = 0;
        document.querySelectorAll('[data-profile-section]').forEach(section => {
            let count = 0;
            section.querySelectorAll('[data-profile-field]').forEach(field => {
                const visible = !keyword || field.textContent.toLocaleLowerCase('id').includes(keyword);
                field.hidden = !visible;
                if (visible) count++;
            });
            section.hidden = count === 0; matches += count;
        });
        document.getElementById('profile-search-status').textContent = keyword ? `${matches} kolom sesuai pencarian.` : '';
    });
</script>

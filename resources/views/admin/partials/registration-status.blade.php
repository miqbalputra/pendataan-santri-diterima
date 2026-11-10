<span class="inline-block rounded-lg px-2.5 py-1 text-xs font-bold {{ match($santri->status_pendaftaran) { 'Diterima' => 'bg-emerald-50 text-emerald-800', 'Ditolak' => 'bg-rose-50 text-rose-800', default => 'bg-amber-50 text-amber-800' } }}">{{ match($santri->status_pendaftaran) { 'Diterima' => 'Sudah diverifikasi', 'Ditolak' => 'Tidak dilanjutkan', default => 'Menunggu verifikasi' } }}</span>
<p class="mt-2 text-xs {{ $santri->followup_sudah_dihubungi ? 'text-slate-500' : 'text-indigo-700' }}">{{ $santri->followup_sudah_dihubungi ? '✓ Sudah dihubungi' : '○ Belum dihubungi' }}</p>
<p class="mt-1 text-xs {{ $santri->followup_sudah_masuk_grup ? 'text-slate-500' : 'text-orange-700' }}">{{ $santri->followup_sudah_masuk_grup ? '✓ Masuk grup dikonfirmasi' : '○ Masuk grup belum dikonfirmasi' }}</p>
@if($santri->relationLoaded('latestNotificationLog'))
    @php($lastLog = $santri->latestNotificationLog)
    <p class="mt-2 text-xs {{ $lastLog?->status === 'failed' ? 'text-rose-700' : 'text-slate-400' }}">Webhook terakhir: {{ match($lastLog?->status) { 'success' => 'diterima layanan', 'failed' => 'gagal', 'skipped' => 'dilewati', default => 'belum ada catatan' } }}</p>
@endif

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman hasil" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        @if ($paginator->onFirstPage())<span class="rounded-lg border border-slate-200 px-4 py-3 text-slate-400" aria-disabled="true">← Sebelumnya</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg border border-slate-200 bg-white px-4 py-3 font-semibold hover:bg-emerald-50">← Sebelumnya</a>@endif
        <div class="hidden md:flex gap-1">
            @foreach ($elements as $element)
                @if (is_string($element))<span class="px-2 py-3 text-slate-400">{{ $element }}</span>@endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())<span aria-current="page" class="rounded-lg bg-emerald-700 text-white px-4 py-3 font-bold">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Buka halaman {{ $page }}" class="rounded-lg px-4 py-3 border border-slate-200 hover:bg-emerald-50">{{ $page }}</a>@endif
                    @endforeach
                @endif
            @endforeach
        </div>
        <span class="text-xs text-slate-500 md:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg border border-slate-200 bg-white px-4 py-3 font-semibold hover:bg-emerald-50">Berikutnya →</a>@else<span class="rounded-lg border border-slate-200 px-4 py-3 text-slate-400" aria-disabled="true">Berikutnya →</span>@endif
    </nav>
@endif

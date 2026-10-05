@if ($paginator->hasPages())
    <nav aria-label="الصفحات" class="flex flex-wrap justify-center gap-2">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="inline-flex size-11 items-center justify-center text-muted">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="inline-flex size-11 items-center justify-center rounded-xl bg-ink font-bold text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="inline-flex size-11 items-center justify-center rounded-xl border border-line bg-white text-text no-underline hover:border-gold">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </nav>
@endif

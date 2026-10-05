<div class="flex flex-wrap gap-6">
    <div class="relative min-h-[380px] min-w-0 flex-[1_1_520px] overflow-hidden rounded-[28px] bg-[#ECE6DB] shadow-[inset_0_0_0_1px_#E0D8C9]">
        <svg width="100%" height="100%" viewBox="0 0 600 420" preserveAspectRatio="xMidYMid slice" fill="none" role="img" aria-label="خريطة الفروع (مكان الخريطة التفاعلية)" class="absolute inset-0">
            <path d="M-20 300 C120 260 220 330 360 290 S560 250 640 270" stroke="#D5E2E6" stroke-width="40"/>
            <g stroke="#FFFFFF" stroke-linecap="round"><path d="M0 120 C150 100 260 160 600 130" stroke-width="14"/><path d="M200 0 C220 140 180 260 240 420" stroke-width="10"/><path d="M430 0 L470 420" stroke-width="7"/><path d="M0 200 L600 220" stroke-width="5"/><path d="M90 0 L120 420" stroke-width="4"/><path d="M320 0 C330 120 300 220 330 420" stroke-width="4"/></g>
            @foreach ([[230, 140], [455, 205], [140, 295]] as [$cx, $cy])
                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="26" fill="#C8A04B" fill-opacity=".18"/><circle cx="{{ $cx }}" cy="{{ $cy }}" r="13" fill="#12100C" stroke="#F6F3EE" stroke-width="3"/><circle cx="{{ $cx }}" cy="{{ $cy }}" r="4.5" fill="#E6C77A"/>
            @endforeach
        </svg>
        <span class="absolute bottom-4 start-4 rounded-xl bg-white/90 px-3 py-2 text-[13px] text-muted">[خريطة جوجل التفاعلية]</span>
    </div>
    <div class="flex min-w-0 flex-[1_1_360px] flex-col gap-3.5">
        @foreach ($branches as $branch)
            <div class="card flex flex-col gap-3 p-[22px]">
                <div class="flex items-center justify-between gap-2"><b class="font-display text-xl">{{ $branch->fullName() }}</b><span class="pill {{ $branch->isOpen() ? 'pill-green' : 'pill-neutral' }}">{{ $branch->statusLabel() }}</span></div>
                <span class="text-[15px] leading-relaxed text-muted">{{ $branch->address }} · {{ $branch->hoursLabel() }}</span>
                <div class="flex flex-wrap gap-2.5">
                    <a href="{{ $branch->map_url ?: '#' }}" class="btn btn-sm btn-ink" @if ($branch->map_url) target="_blank" rel="noopener" @endif><x-icon name="pin" :size="16" />الاتجاهات</a>
                    <a href="tel:{{ preg_replace('/\D/', '', (string) $branch->phone) }}" class="btn btn-sm btn-outline"><x-icon name="phone" :size="16" />{{ $branch->phone }}</a>
                </div>
            </div>
        @endforeach
    </div>
</div>

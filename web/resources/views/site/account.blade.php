<x-layouts.site title="طلباتي">
    <x-page-head title="طلباتي" lead="سبائكك، وحجوزات المجوهرات، ومعادات البيع. وري كود الطلب في الفرع.">
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline"><x-icon name="logout" :size="18" />خروج</button></form>
    </x-page-head>
    <div class="container-site flex flex-col gap-10 py-10 pb-20">
        <x-flash />
        @foreach (['الحالية' => $open, 'السابقة' => $past] as $heading => $list)
            <section class="flex flex-col gap-4">
                <h2 class="m-0 font-display text-2xl">{{ $heading }} <span class="text-base font-normal text-muted">({{ $list->count() }})</span></h2>
                @forelse ($list as $order)
                    <a href="{{ route('orders.show', $order) }}" class="lift card flex items-center gap-4 p-3.5 text-text no-underline">
                        <span class="pedestal flex size-[72px] flex-none items-center justify-center rounded-2xl"><img src="{{ $order->imageSrc() }}" alt="" class="size-16 object-contain"></span>
                        <span class="flex min-w-0 flex-1 flex-col gap-1">
                            <span class="flex flex-wrap items-center gap-2"><x-status-pill :status="$order->status" /><span class="text-xs text-muted">{{ $order->type->label() }}</span></span>
                            <b class="text-[17px]">{{ $order->title() }}</b>
                            <span class="text-[13px] text-muted">{{ $order->branch->fullName() }} · <span dir="ltr">{{ $order->code }}</span>@if ($order->slot_at) · {{ $order->slot_at->locale('ar')->translatedFormat('l j F، g:i a') }}@endif</span>
                        </span>
                        <b class="text-gold-dark max-sm:hidden">{{ number_format((float) $order->total) }} ج.م</b>
                        <x-icon name="chevron" class="text-gold-dark" />
                    </a>
                @empty
                    <div class="card flex flex-col items-center gap-3 p-10 text-center">
                        <b>{{ $heading === 'الحالية' ? 'مفيش طلبات جارية' : 'لسه مفيش طلبات قديمة' }}</b>
                        @if ($heading === 'الحالية')<div class="flex flex-wrap justify-center gap-2"><a href="{{ route('bullion') }}" class="btn btn-gold">اطلب سبيكة</a><a href="{{ route('shop.index') }}" class="btn btn-outline">تصفّح المجوهرات</a></div>@endif
                    </div>
                @endforelse
            </section>
        @endforeach
    </div>
</x-layouts.site>

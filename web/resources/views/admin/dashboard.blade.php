@php
    $delta = function ($now, $before) {
        if (! $before) return [$now > 0 ? 'جديد النهارده' : 'نفس امبارح', 'text-muted'];
        $p = round(($now - $before) / $before * 100);
        return [($p >= 0 ? '▲ ' : '▼ ').abs($p).'% عن امبارح', $p >= 0 ? 'text-up' : 'text-down'];
    };
    $max = max($daily->max('v'), 1);
@endphp
<x-layouts.admin title="نظرة عامة">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h1 class="m-0 font-display text-[2rem] font-bold">صباح الخير</h1><p class="m-0 text-muted">{{ now()->locale('ar')->translatedFormat('l j F Y') }} · كل الفروع</p></div>
        <a href="{{ route('admin.prices') }}" class="btn btn-sm btn-outline"><x-icon name="tag" :size="18" />عيار 21 الآن {{ number_format(app(\App\Services\GoldPricing::class)->sell('21')) }}</a>
    </div>

    <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(210px,1fr))]">
        @php [$t, $c] = $delta(...$kpis['bullion']); @endphp
        <div class="card flex flex-col gap-1.5 p-5"><span class="text-sm text-muted">سبائك اتطلبت النهارده</span><b class="text-3xl">{{ rtrim(rtrim(number_format($kpis['bullion'][0], 1), '0'), '.') }} <span class="text-[15px] font-normal text-muted">جم</span></b><span class="text-[13px] font-semibold {{ $c }}">{{ $t }}</span></div>
        @php [$t, $c] = $delta(...$kpis['sell']); @endphp
        <div class="card flex flex-col gap-1.5 p-5"><span class="text-sm text-muted">ذهب جاي للبيع النهارده</span><b class="text-3xl">{{ rtrim(rtrim(number_format($kpis['sell'][0], 1), '0'), '.') }} <span class="text-[15px] font-normal text-muted">جم</span></b><span class="text-[13px] font-semibold {{ $c }}">{{ $t }}</span></div>
        <div class="card flex flex-col gap-1.5 p-5"><span class="text-sm text-muted">حجوزات مجوهرات مفتوحة</span><b class="text-3xl">{{ $kpis['reservations'] }}</b><span class="text-[13px] text-muted">{{ $kpis['ready'] }} طلب جاهز للاستلام</span></div>
        <a href="{{ route('admin.orders.index', ['status' => 'new']) }}" class="flex flex-col gap-1.5 rounded-3xl border border-[#F0D9A8] bg-warn-bg p-5 text-warn no-underline"><span class="text-sm">طلبات جديدة مستنية تأكيد</span><b class="text-3xl">{{ $kpis['new'] }}</b><span class="text-[13px] font-bold">راجعها دلوقتي ←</span></a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <section class="card flex min-w-0 flex-col gap-4 p-6" x-data="{ hover: null, daily: @js($daily) }" aria-labelledby="daily-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2"><h2 id="daily-title" class="m-0 text-lg font-bold">السبائك المطلوبة يومياً (جم)</h2><span class="text-[13px] text-muted">آخر 14 يوم</span></div>
            <div class="min-h-[22px] text-sm" aria-live="polite"><b x-text="(hover === null ? daily[daily.length - 1].v : daily[hover].v) + ' جم'"></b> <span class="text-muted" x-text="'· ' + (hover === null ? daily[daily.length - 1].label : daily[hover].label)"></span></div>
            <div class="flex min-h-[220px] flex-1 items-end gap-1.5 border-b border-line" dir="ltr" @mouseleave="hover = null">
                @foreach ($daily as $i => $d)
                    <button type="button" class="group flex h-full flex-1 items-end" @mouseenter="hover = {{ $i }}" @focus="hover = {{ $i }}" @click="hover = {{ $i }}" aria-label="{{ $d['label'] }}: {{ $d['v'] }} جم">
                        <span class="w-full rounded-t-[4px] bg-gold-deep transition group-hover:bg-gold-dark" :class="hover === {{ $i }} ? 'bg-gold-dark' : ''" style="height: {{ max($d['v'] / $max * 100, 1.5) }}%"></span>
                    </button>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-muted" dir="ltr"><span>{{ $daily->first()['label'] }}</span><span>{{ $daily->last()['label'] }}</span></div>
        </section>
        <div class="flex flex-col gap-5">
            <section class="card flex flex-col gap-3 p-6">
                <h2 class="m-0 text-lg font-bold">الطلبات حسب الفرع</h2>
                @php $bmax = max($byBranch->max('orders'), 1); @endphp
                @foreach ($byBranch as $b)
                    <div class="flex flex-col gap-1.5"><div class="flex justify-between text-sm"><span>{{ $b['name'] }}</span><b>{{ $b['orders'] }}</b></div><div class="h-2 rounded-full bg-sand"><div class="h-2 rounded-full bg-gold-deep" style="width: {{ $b['orders'] / $bmax * 100 }}%"></div></div></div>
                @endforeach
            </section>
            <section class="card flex flex-col gap-2.5 p-6">
                <h2 class="m-0 text-lg font-bold">مخزون قرب يخلص</h2>
                @forelse ($lowStock as $row)
                    <div class="flex justify-between gap-2 text-sm"><span>{{ $row->name }} · {{ $row->branch }}</span><span class="font-semibold {{ $row->quantity ? 'text-warn' : 'text-down' }}">{{ $row->quantity ? 'قطعة واحدة' : 'نفدت' }}</span></div>
                @empty
                    <p class="m-0 text-sm text-muted">كل حاجة متوفرة.</p>
                @endforelse
                <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold">المخزون ←</a>
            </section>
        </div>
    </div>

    <section class="card flex flex-col gap-3 p-6">
        <div class="flex items-center justify-between"><h2 class="m-0 text-lg font-bold">آخر الطلبات</h2><a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold">كل الطلبات ←</a></div>
        @include('admin.orders.table', ['orders' => $latest])
    </section>
</x-layouts.admin>

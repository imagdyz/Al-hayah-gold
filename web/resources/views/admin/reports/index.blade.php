@php
    $g = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $range = $from->isSameDay($to) ? $from->locale('ar')->translatedFormat('l j F Y') : $from->format('Y/m/d').' ← '.$to->format('Y/m/d');
@endphp
<x-layouts.admin title="التقارير">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h1 class="m-0 font-display text-[2rem] font-bold">التقارير</h1><p class="m-0 text-muted">{{ $range }} · الفواتير الملغاة مش محسوبة</p></div>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline print:hidden"><x-icon name="printer" :size="17" />اطبع التقرير</button>
    </div>
    <form method="GET" class="card flex flex-wrap items-end gap-3 p-4 print:hidden">
        <div><label for="from" class="label">من</label><input id="from" name="from" type="date" value="{{ $from->toDateString() }}" class="field"></div>
        <div><label for="to" class="label">إلى</label><input id="to" name="to" type="date" value="{{ $to->toDateString() }}" class="field"></div>
        <button class="btn btn-ink">اعرض</button>
        <div class="flex flex-wrap gap-2">
            <a class="chip" href="{{ route('admin.reports') }}">النهارده</a>
            <a class="chip" href="{{ route('admin.reports', ['from' => today()->subDays(6)->toDateString(), 'to' => today()->toDateString()]) }}">آخر 7 أيام</a>
            <a class="chip" href="{{ route('admin.reports', ['from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()]) }}">الشهر ده</a>
        </div>
    </form>

    <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <div class="card flex flex-col gap-1 p-5"><span class="text-sm text-muted">المبيعات</span><b class="text-3xl">{{ number_format($r['sales']) }} <span class="text-[15px] font-normal text-muted">ج.م</span></b><span class="text-[13px] text-muted">{{ $r['pieces'] }} قطعة في {{ $r['sale_invoices'] }} فاتورة</span></div>
        <div class="card flex flex-col gap-1 p-5"><span class="text-sm text-muted">منها مصنعية</span><b class="text-3xl">{{ number_format($r['making']) }} <span class="text-[15px] font-normal text-muted">ج.م</span></b></div>
        <div class="card flex flex-col gap-1 p-5"><span class="text-sm text-muted">ذهب كسر اتشرى</span><b class="text-3xl">{{ number_format($r['purchases']) }} <span class="text-[15px] font-normal text-muted">ج.م</span></b><span class="text-[13px] text-muted">{{ $g($r['bought_by_karat']->sum('weight')) }} جم بعد الخصم</span></div>
        <div class="card-ink flex flex-col gap-1 rounded-3xl p-5"><span class="text-sm text-muted-dark">المكسب التقديري</span><b class="text-3xl">{{ number_format($r['profit']) }} <span class="text-[15px] font-normal text-muted-dark">ج.م</span></b><span class="text-[13px] text-gold-bright">@if ($r['uncosted']){{ $r['uncosted'] }} قطعة من غير تكلفة مش محسوبة @else البيع ناقص تكلفة القطع @endif</span></div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="card overflow-hidden">
            <h2 class="m-0 px-5 pt-5 text-lg font-bold">المبيوع حسب العيار</h2>
            <div class="overflow-x-auto p-2"><table class="w-full border-collapse text-sm">
                <thead><tr class="bg-paper text-[13px] text-muted"><th scope="col" class="px-3 py-2.5 text-start font-medium">العيار</th><th scope="col" class="px-3 py-2.5 text-start font-medium">القطع</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الوزن (جم)</th><th scope="col" class="px-3 py-2.5 text-start font-medium">القيمة</th></tr></thead>
                <tbody>
                    @forelse ($r['sold_by_karat'] as $row)
                        <tr class="border-t border-line"><td class="px-3 py-2.5">{{ $row->karat }}</td><td class="px-3 py-2.5">{{ $row->n }}</td><td class="px-3 py-2.5">{{ $g($row->weight) }}</td><td class="px-3 py-2.5 font-bold">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-muted">مفيش مبيعات في الفترة دي.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
        <section class="card overflow-hidden">
            <h2 class="m-0 px-5 pt-5 text-lg font-bold">الكسر المشترى حسب العيار</h2>
            <div class="overflow-x-auto p-2"><table class="w-full border-collapse text-sm">
                <thead><tr class="bg-paper text-[13px] text-muted"><th scope="col" class="px-3 py-2.5 text-start font-medium">العيار</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الوزن الإجمالي</th><th scope="col" class="px-3 py-2.5 text-start font-medium">وزن التحييف</th><th scope="col" class="px-3 py-2.5 text-start font-medium">القيمة</th></tr></thead>
                <tbody>
                    @forelse ($r['bought_by_karat'] as $row)
                        <tr class="border-t border-line"><td class="px-3 py-2.5">{{ $row->karat }}</td><td class="px-3 py-2.5">{{ $g($row->gross) }}</td><td class="px-3 py-2.5">{{ $g($row->weight) }}</td><td class="px-3 py-2.5 font-bold">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-muted">مفيش ذهب اتشرى في الفترة دي.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
        <section class="card overflow-hidden">
            <h2 class="m-0 px-5 pt-5 text-lg font-bold">الصافي حسب طريقة التسوية</h2>
            <div class="overflow-x-auto p-2"><table class="w-full border-collapse text-sm">
                <thead><tr class="bg-paper text-[13px] text-muted"><th scope="col" class="px-3 py-2.5 text-start font-medium">الطريقة</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الفواتير</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الصافي (ج.م)</th></tr></thead>
                <tbody>
                    @foreach (\App\Enums\Settlement::cases() as $s)
                        @php $row = $r['by_settlement'][$s->value] ?? null; @endphp
                        <tr class="border-t border-line"><td class="px-3 py-2.5">{{ $s->label() }}</td><td class="px-3 py-2.5">{{ $row->n ?? 0 }}</td><td class="px-3 py-2.5 font-bold">{{ number_format($row->net ?? 0) }}</td></tr>
                    @endforeach
                    <tr class="border-t border-line-strong bg-paper"><td class="px-3 py-2.5 font-bold">الإجمالي</td><td class="px-3 py-2.5">{{ $r['invoices'] }}</td><td class="px-3 py-2.5 font-bold">{{ number_format($r['net']) }}</td></tr>
                </tbody>
            </table></div>
            <p class="m-0 px-5 pb-4 text-xs text-muted">الصافي = البيع ناقص الشراء. الرقم بالسالب معناه إن المحل دفع للعملاء أكتر ما استلم.</p>
        </section>
        <section class="card overflow-hidden">
            <h2 class="m-0 px-5 pt-5 text-lg font-bold">المخزون دلوقتي</h2>
            <div class="overflow-x-auto p-2"><table class="w-full border-collapse text-sm">
                <thead><tr class="bg-paper text-[13px] text-muted"><th scope="col" class="px-3 py-2.5 text-start font-medium">العيار</th><th scope="col" class="px-3 py-2.5 text-start font-medium">القطع</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الوزن (جم)</th><th scope="col" class="px-3 py-2.5 text-start font-medium">القيمة بسعر النهارده</th></tr></thead>
                <tbody>
                    @foreach ($stock['rows'] as $row)
                        <tr class="border-t border-line"><td class="px-3 py-2.5">{{ $row['karat'] }}</td><td class="px-3 py-2.5">{{ $row['n'] }}</td><td class="px-3 py-2.5">{{ $g($row['weight']) }}</td><td class="px-3 py-2.5 font-bold">{{ number_format($row['value']) }}</td></tr>
                    @endforeach
                    <tr class="border-t border-line-strong bg-paper"><td class="px-3 py-2.5 font-bold">الإجمالي</td><td class="px-3 py-2.5">{{ $stock['n'] }}</td><td class="px-3 py-2.5">{{ $g($stock['weight']) }}</td><td class="px-3 py-2.5 font-bold">{{ number_format($stock['value']) }}</td></tr>
                </tbody>
            </table></div>
        </section>
    </div>

    @if ($r['daily']->count() > 1)
        <section class="card overflow-hidden">
            <h2 class="m-0 px-5 pt-5 text-lg font-bold">يوم بيوم</h2>
            <div class="overflow-x-auto p-2"><table class="w-full border-collapse text-sm">
                <thead><tr class="bg-paper text-[13px] text-muted"><th scope="col" class="px-3 py-2.5 text-start font-medium">اليوم</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الفواتير</th><th scope="col" class="px-3 py-2.5 text-start font-medium">البيع</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الشراء</th><th scope="col" class="px-3 py-2.5 text-start font-medium">الصافي</th></tr></thead>
                <tbody>
                    @foreach ($r['daily'] as $d)
                        <tr class="border-t border-line"><td class="px-3 py-2.5">{{ \Illuminate\Support\Carbon::parse($d->day)->locale('ar')->translatedFormat('l j F') }}</td><td class="px-3 py-2.5">{{ $d->n }}</td><td class="px-3 py-2.5">{{ number_format($d->sales) }}</td><td class="px-3 py-2.5">{{ number_format($d->purchases) }}</td><td class="px-3 py-2.5 font-bold">{{ number_format($d->net) }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif
</x-layouts.admin>

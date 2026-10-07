<x-layouts.admin :title="'فاتورة '.$invoice->label()">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-col gap-1">
            <a href="{{ route('admin.invoices.index') }}" class="text-sm">← الفواتير</a>
            <h1 class="m-0 font-display text-[2rem] font-bold">فاتورة رقم <span dir="ltr">{{ $invoice->label() }}</span> <span class="pill pill-{{ $invoice->status->tone() }} align-middle text-sm">{{ $invoice->status->label() }}</span></h1>
            <p class="m-0 text-sm text-muted">{{ $invoice->issued_at->locale('ar')->translatedFormat('l j F Y · g:i a') }} · الكاشير: {{ $invoice->user?->displayName() ?? '—' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.print', [$invoice, 'format' => 'a5']) }}" target="_blank" class="btn btn-gold"><x-icon name="printer" :size="18" />اطبع A5</a>
            <a href="{{ route('admin.invoices.print', [$invoice, 'format' => '80mm']) }}" target="_blank" class="btn btn-outline"><x-icon name="printer" :size="18" />اطبع إيصال 80 مم</a>
            <a href="{{ route('admin.pos') }}" class="btn btn-ink"><x-icon name="plus" :size="18" />فاتورة جديدة</a>
        </div>
    </div>
    @if ($invoice->isVoided())
        <div role="alert" class="rounded-2xl bg-down-bg px-4 py-3 text-sm text-[#8C1D18]">اتلغت {{ $invoice->voided_at?->format('Y-m-d g:i a') }}. السبب: {{ $invoice->void_reason }}</div>
    @endif

    <div class="grid items-start gap-5 xl:grid-cols-[1fr_320px]">
        <div class="flex min-w-0 flex-col gap-5">
            @foreach (['sales' => 'فاتورة بيع', 'purchases' => 'فاتورة الشراء'] as $kind => $title)
                @php $rows = $invoice->lines->where('kind', $kind === 'sales' ? \App\Enums\LineKind::Sale : \App\Enums\LineKind::Purchase)->values(); @endphp
                @continue($rows->isEmpty())
                <section class="card overflow-hidden">
                    <h2 class="m-0 px-5 pt-5 text-lg font-bold">{{ $title }}</h2>
                    <div class="overflow-x-auto p-2">
                        <table class="w-full min-w-[640px] border-collapse text-sm">
                            <thead><tr class="bg-paper text-[13px] text-muted">
                                <th scope="col" class="px-3 py-2.5 text-start font-medium">م</th>
                                <th scope="col" class="px-3 py-2.5 text-start font-medium">البيان</th>
                                <th scope="col" class="px-3 py-2.5 text-start font-medium">العيار</th>
                                @if ($kind === 'sales')
                                    <th scope="col" class="px-3 py-2.5 text-start font-medium">الوزن</th>
                                    <th scope="col" class="px-3 py-2.5 text-start font-medium">سعر الجرام</th>
                                    <th scope="col" class="px-3 py-2.5 text-start font-medium">المصنعية</th>
                                @else
                                    <th scope="col" class="px-3 py-2.5 text-start font-medium">الوزن الإجمالي</th>
                                    <th scope="col" class="px-3 py-2.5 text-start font-medium">وزن التحييف</th>
                                    <th scope="col" class="px-3 py-2.5 text-start font-medium">سعر الشراء</th>
                                @endif
                                <th scope="col" class="px-3 py-2.5 text-start font-medium">الإجمالي</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($rows as $i => $l)
                                    <tr class="border-t border-line">
                                        <td class="px-3 py-2.5">{{ $i + 1 }}</td>
                                        <td class="px-3 py-2.5">{{ $l->description }}@if ($l->piece)<div class="text-xs text-muted" dir="ltr"><a href="{{ route('admin.pieces.index', ['q' => $l->piece->barcode, 'status' => 'all']) }}">{{ $l->piece->barcode }}</a></div>@endif</td>
                                        <td class="px-3 py-2.5">{{ $l->karat }}</td>
                                        @if ($kind === 'sales')
                                            <td class="px-3 py-2.5" dir="ltr">{{ $l->net_weight }}</td>
                                            <td class="px-3 py-2.5">{{ number_format($l->gram_price) }}</td>
                                            <td class="px-3 py-2.5">{{ number_format($l->making_fee) }}</td>
                                        @else
                                            <td class="px-3 py-2.5" dir="ltr">{{ $l->gross_weight }}</td>
                                            <td class="px-3 py-2.5" dir="ltr">{{ $l->net_weight }}</td>
                                            <td class="px-3 py-2.5">{{ number_format($l->gram_price) }}</td>
                                        @endif
                                        <td class="px-3 py-2.5 font-bold">{{ number_format($l->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
        <aside class="flex flex-col gap-5">
            <section class="card-ink flex flex-col gap-3 rounded-3xl p-6">
                <div class="flex justify-between text-sm"><span class="text-muted-dark">إجمالي البيع</span><b>{{ number_format($invoice->sales_total) }} ج.م</b></div>
                <div class="flex justify-between text-sm"><span class="text-muted-dark">إجمالي الشراء</span><b>{{ number_format($invoice->purchases_total) }} ج.م</b></div>
                <div class="flex items-end justify-between gap-2 border-t border-ink-3 pt-3"><span class="text-gold-bright">{{ $invoice->netLabel() }}</span><b class="text-3xl">{{ number_format(abs($invoice->net)) }} ج.م</b></div>
            </section>
            <section class="card flex flex-col gap-2 p-6 text-sm">
                <div class="flex justify-between gap-3"><span class="text-muted">العميل</span><b>{{ $invoice->customer_name ?: '—' }}</b></div>
                <div class="flex justify-between gap-3"><span class="text-muted">الموبايل</span><b dir="ltr">{{ $invoice->customer_phone ?: '—' }}</b></div>
                <div class="flex justify-between gap-3"><span class="text-muted">طريقة التسوية</span><b>{{ $invoice->settlement->label() }}</b></div>
                @if ($invoice->base_24)<div class="flex justify-between gap-3"><span class="text-muted">سعر 24 الأساسي وقتها</span><b>{{ number_format($invoice->base_24) }}</b></div>@endif
                @if ($invoice->notes)<p class="m-0 border-t border-line pt-2">{{ $invoice->notes }}</p>@endif
            </section>
            @unless ($invoice->isVoided())
                <details class="card p-5">
                    <summary class="cursor-pointer text-sm font-bold text-down">إلغاء الفاتورة</summary>
                    <form method="POST" action="{{ route('admin.invoices.void', $invoice) }}" class="mt-3 flex flex-col gap-3">
                        @csrf
                        <p class="m-0 text-sm text-muted">القطع المبيوعة هترجع المخزون، والفاتورة هتفضل متسجلة بحالة "ملغاة".</p>
                        <div><label for="reason" class="label">سبب الإلغاء</label><input id="reason" name="reason" required maxlength="200" class="field"></div>
                        <button class="btn btn-outline text-down">ألغِ الفاتورة</button>
                    </form>
                </details>
            @endunless
        </aside>
    </div>
</x-layouts.admin>

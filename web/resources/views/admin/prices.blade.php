<x-layouts.admin title="الأسعار والهوامش">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h1 class="m-0 font-display text-[2rem] font-bold">الأسعار والهوامش</h1>
            <p class="m-0 text-muted">المصدر: {{ ['manual' => 'إدخال يدوي', 'daleelak' => 'دليلك (تلقائي كل دقيقة)', 'http' => 'API خارجي'][$source] ?? $source }} · آخر تحديث {{ optional($updatedAt)->locale('ar')->diffForHumans() }}</p></div>
        <form method="POST" action="{{ route('admin.prices.halt') }}">@csrf
            <button class="btn {{ $halted ? 'btn-gold' : 'bg-down text-white' }}"><x-icon name="pause" :size="18" />{{ $halted ? 'رجّع الطلبات أونلاين' : 'وقّف الطلبات أونلاين' }}</button>
        </form>
    </div>
    @if ($halted)
        <div class="rounded-2xl bg-warn-bg px-4 py-3 text-warn">الطلبات أونلاين واقفة دلوقتي. العملاء شايفين الأسعار بس.@if ($haltReason) <b>السبب:</b> {{ $haltReason }}@endif</div>
    @endif

    <form method="POST" action="{{ route('admin.prices.update') }}" class="flex flex-col gap-6">
        @csrf @method('PUT')
        <div class="grid gap-5 lg:grid-cols-[360px_1fr]">
            <section class="card-ink flex flex-col gap-4 p-6">
                <h2 class="m-0 text-lg font-bold">سعر جرام 24 الأساسي</h2>
                <p class="m-0 text-sm text-muted-dark">السعر العالمي قبل هوامشنا. باقي الأعيرة بتتحسب منه (21 = ×21/24، 18 = ×18/24، الجنيه = 8 جم عيار 21).</p>
                <label for="base" class="sr-only">سعر جرام 24 الأساسي</label>
                <div class="flex items-center gap-2"><input id="base" name="base_24" type="number" step="0.01" value="{{ old('base_24', $base) }}" dir="ltr" class="field field-ink text-right text-2xl font-bold"><span class="text-gold">ج.م</span></div>
                <div class="flex flex-col gap-1 border-t border-ink-3 pt-3 text-xs text-muted-dark">
                    @foreach ($recent as $r)
                        <div class="flex justify-between"><span>{{ $r->recorded_at->locale('ar')->translatedFormat('j M، g:i a') }}</span><span>{{ number_format($r->base_24, 2) }} · {{ $r->source }}</span></div>
                    @endforeach
                </div>
            </section>

            <section class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] border-collapse text-sm">
                        <thead><tr class="bg-paper text-[13px] text-muted"><th scope="col" class="px-4 py-3 text-start font-medium">العيار</th><th scope="col" class="text-start font-medium">هامش البيع (+)</th><th scope="col" class="text-start font-medium">هامش الشراء (−)</th><th scope="col" class="text-start font-medium">نبيع بـ</th><th scope="col" class="text-start font-medium">نشتري بـ</th></tr></thead>
                        <tbody>
                            @foreach ($quotes as $k => $q)
                                <tr class="border-t border-line">
                                    <th scope="row" class="px-4 py-3 text-start">{{ $q['label'] }}</th>
                                    <td><label class="sr-only" for="ms-{{ $k }}">هامش البيع {{ $q['label'] }}</label><input id="ms-{{ $k }}" name="margins[{{ $k }}][sell]" type="number" step="0.5" min="0" value="{{ old("margins.$k.sell", $margins[$k]->sell_margin ?? 0) }}" class="field w-28 min-h-10" dir="ltr"></td>
                                    <td><label class="sr-only" for="mb-{{ $k }}">هامش الشراء {{ $q['label'] }}</label><input id="mb-{{ $k }}" name="margins[{{ $k }}][buy]" type="number" step="0.5" min="0" value="{{ old("margins.$k.buy", $margins[$k]->buy_margin ?? 0) }}" class="field w-28 min-h-10" dir="ltr"></td>
                                    <td class="font-bold">{{ number_format($q['sell']) }}</td>
                                    <td class="font-bold">{{ number_format($q['buy']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="m-0 border-t border-line px-4 py-3 text-xs text-muted">الأسعار النهائية بتتقرّب لأقرب 5 جنيه. المصنعية بتتحط على كل منتج من صفحة المنتجات.</p>
            </section>
        </div>

        <section class="card grid gap-5 p-6 sm:grid-cols-3">
            <div><label for="lock" class="label">مدة تثبيت سعر السبائك (دقيقة)</label><input id="lock" name="price_lock_minutes" type="number" min="1" value="{{ old('price_lock_minutes', $settings['price_lock_minutes']) }}" class="field"></div>
            <div><label for="dep" class="label">نسبة العربون (%)</label><input id="dep" name="deposit_percent" type="number" min="0" max="100" value="{{ old('deposit_percent', $settings['deposit_percent']) }}" class="field"></div>
            <div><label for="hold" class="label">مدة حجز المجوهرات (ساعة)</label><input id="hold" name="reservation_hours" type="number" min="1" value="{{ old('reservation_hours', $settings['reservation_hours']) }}" class="field"></div>
        </section>
        <div><button class="btn btn-lg btn-gold">احفظ ونزّل الأسعار</button></div>
    </form>
</x-layouts.admin>

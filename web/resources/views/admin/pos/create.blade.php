<x-layouts.admin title="الكاشير">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="m-0 font-display text-[2rem] font-bold">فاتورة جديدة</h1>
            <p class="m-0 text-sm text-muted">أسعار النهارده{{ $updatedAt ? ' · آخر تحديث '.$updatedAt->format('H:i') : '' }} · تقدر تغيّر السعر في أي سطر</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($prices as $k => $p)
                <span class="pill pill-neutral">عيار {{ $k }}: بيع {{ number_format($p['sell']) }} · شراء {{ number_format($p['buy']) }}</span>
            @endforeach
        </div>
    </div>
    @if ($halted)
        <div role="alert" class="rounded-2xl bg-warn-bg px-4 py-3 text-sm text-warn">طلبات الموقع واقفة دلوقتي، والسعر ممكن يكون قديم. راجع سعر الجرام قبل ما تسجّل الفاتورة.</div>
    @endif

    <div x-data="pos({ prices: @js($prices), lookupUrl: '{{ route('admin.pos.piece') }}', storeUrl: '{{ route('admin.pos.store') }}', csrf: '{{ csrf_token() }}' })"
         class="grid items-start gap-5 xl:grid-cols-[1fr_340px]">
        <div class="flex min-w-0 flex-col gap-5">
            <section class="card flex flex-col gap-4 p-5 sm:p-6" aria-labelledby="sale-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="sale-title" class="m-0 text-lg font-bold">فاتورة بيع</h2>
                    <form class="flex w-full max-w-[420px] gap-2" @submit.prevent="scan()">
                        <label for="barcode" class="sr-only">الباركود</label>
                        <input id="barcode" x-ref="barcode" x-model="barcode" autofocus autocomplete="off" dir="ltr" placeholder="امسح الباركود أو اكتبه" class="field min-h-11 flex-1">
                        <button class="btn btn-ink min-h-11"><x-icon name="barcode" :size="18" />ضيف</button>
                    </form>
                </div>
                <p x-show="lookupError" x-cloak x-text="lookupError" role="alert" class="m-0 rounded-xl bg-down-bg px-3 py-2 text-sm text-[#8C1D18]"></p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] border-collapse text-sm">
                        <thead><tr class="bg-paper text-[13px] text-muted">
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">م</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">البيان</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">العيار</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">الوزن (جم)</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">سعر الجرام</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">المصنعية</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">الإجمالي</th>
                            <th scope="col"><span class="sr-only">شيل</span></th>
                        </tr></thead>
                        <tbody>
                            <template x-for="(l, i) in sales" :key="l.piece_id">
                                <tr class="border-t border-line">
                                    <td class="px-3 py-2" x-text="i + 1"></td>
                                    <td class="px-3 py-2"><b x-text="l.name"></b><div class="text-xs text-muted" dir="ltr" x-text="l.barcode"></div></td>
                                    <td class="px-3 py-2" x-text="l.karat"></td>
                                    <td class="px-3 py-2" dir="ltr" x-text="Number(l.weight).toFixed(3)"></td>
                                    <td class="px-3 py-2"><input type="number" min="1" step="1" x-model.number="l.gram_price" class="field min-h-10 w-28" dir="ltr" :aria-label="'سعر الجرام للسطر ' + (i + 1)"></td>
                                    <td class="px-3 py-2"><input type="number" min="0" step="1" x-model.number="l.making_fee" class="field min-h-10 w-28" dir="ltr" :aria-label="'المصنعية للسطر ' + (i + 1)"></td>
                                    <td class="px-3 py-2 font-bold" x-text="$fmt(saleTotal(l))"></td>
                                    <td class="px-2"><button type="button" class="text-down" @click="sales.splice(i, 1)" :aria-label="'شيل ' + l.name"><x-icon name="close" :size="18" /></button></td>
                                </tr>
                            </template>
                            <tr x-show="!sales.length" class="border-t border-line"><td colspan="8" class="px-3 py-6 text-center text-muted">امسح باركود القطعة عشان تتضاف هنا.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card flex flex-col gap-4 p-5 sm:p-6" aria-labelledby="buy-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="buy-title" class="m-0 text-lg font-bold">فاتورة الشراء <span class="text-sm font-normal text-muted">(ذهب كسر من العميل)</span></h2>
                    <button type="button" class="btn btn-sm btn-outline" @click="addPurchase()"><x-icon name="plus" :size="17" />ضيف ذهب وارد</button>
                </div>
                <div class="overflow-x-auto" x-show="purchases.length" x-cloak>
                    <table class="w-full min-w-[680px] border-collapse text-sm">
                        <thead><tr class="bg-paper text-[13px] text-muted">
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">بيان الذهب الوارد</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">العيار</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">الوزن الإجمالي</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">وزن التحييف</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">سعر الشراء</th>
                            <th scope="col" class="px-3 py-2.5 text-start font-medium">الإجمالي</th>
                            <th scope="col"><span class="sr-only">شيل</span></th>
                        </tr></thead>
                        <tbody>
                            <template x-for="(p, i) in purchases" :key="i">
                                <tr class="border-t border-line">
                                    <td class="px-3 py-2"><input x-model="p.description" maxlength="120" class="field min-h-10 w-32" :aria-label="'البيان للسطر ' + (i + 1)"></td>
                                    <td class="px-3 py-2"><select x-model.number="p.karat" @change="setKarat(p)" class="field min-h-10 w-20" :aria-label="'العيار للسطر ' + (i + 1)"><option value="24">24</option><option value="21">21</option><option value="18">18</option></select></td>
                                    <td class="px-3 py-2"><input type="number" min="0.001" step="0.001" x-model="p.gross_weight" @input="if (!p.netTouched) p.net_weight = p.gross_weight" class="field min-h-10 w-24" dir="ltr" :aria-label="'الوزن الإجمالي للسطر ' + (i + 1)"></td>
                                    <td class="px-3 py-2"><input type="number" min="0.001" step="0.001" x-model="p.net_weight" @input="p.netTouched = true" class="field min-h-10 w-24" dir="ltr" :aria-label="'وزن التحييف للسطر ' + (i + 1)"></td>
                                    <td class="px-3 py-2"><input type="number" min="1" step="1" x-model.number="p.gram_price" class="field min-h-10 w-24" dir="ltr" :aria-label="'سعر الشراء للسطر ' + (i + 1)"></td>
                                    <td class="px-3 py-2 font-bold" x-text="$fmt(purchaseTotal(p))"></td>
                                    <td class="px-2"><button type="button" class="text-down" @click="purchases.splice(i, 1)" aria-label="شيل السطر"><x-icon name="close" :size="18" /></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p x-show="!purchases.length" class="m-0 text-sm text-muted">لو العميل بايع أو بيبدّل ذهب، ضيفه هنا. وزن التحييف هو الوزن بعد الخصم، وعليه بيتحسب السعر.</p>
            </section>
        </div>

        <aside class="flex flex-col gap-5 xl:sticky xl:top-6">
            <section class="card flex flex-col gap-4 p-5 sm:p-6">
                <h2 class="m-0 text-lg font-bold">العميل</h2>
                <div><label for="cname" class="label">الاسم</label><input id="cname" x-model="customerName" maxlength="120" class="field"></div>
                <div><label for="cphone" class="label">الموبايل</label><input id="cphone" x-model="customerPhone" maxlength="20" inputmode="tel" dir="ltr" class="field"></div>
                <fieldset class="m-0 flex flex-col gap-2 border-0 p-0">
                    <legend class="label">طريقة التسوية</legend>
                    @foreach (\App\Enums\Settlement::cases() as $s)
                        <label class="option"><input type="radio" name="settlement" value="{{ $s->value }}" x-model="settlement" class="accent-gold-deep">{{ $s->label() }}</label>
                    @endforeach
                </fieldset>
                <div><label for="notes" class="label">ملاحظات</label><textarea id="notes" x-model="notes" rows="2" maxlength="500" class="field py-2"></textarea></div>
            </section>
            <section class="card-ink flex flex-col gap-3 rounded-3xl p-5 sm:p-6" aria-live="polite">
                <div class="flex justify-between text-sm"><span class="text-muted-dark">إجمالي البيع</span><b x-text="$fmt(salesTotal) + ' ج.م'"></b></div>
                <div class="flex justify-between text-sm"><span class="text-muted-dark">إجمالي الشراء</span><b x-text="$fmt(purchasesTotal) + ' ج.م'"></b></div>
                <div class="flex items-end justify-between gap-2 border-t border-ink-3 pt-3">
                    <span x-text="netLabel" class="text-gold-bright"></span>
                    <b class="text-3xl" x-text="$fmt(Math.abs(net)) + ' ج.م'"></b>
                </div>
                <template x-if="errors.length"><ul role="alert" class="m-0 list-none rounded-xl bg-down-bg p-3 text-sm text-[#8C1D18]"><template x-for="e in errors"><li x-text="e"></li></template></ul></template>
                <button type="button" class="btn btn-lg btn-gold" @click="submit()" :disabled="busy"><x-icon name="receipt" :size="19" /><span x-text="busy ? 'بيتسجّل…' : 'سجّل الفاتورة'"></span></button>
            </section>
        </aside>
    </div>
</x-layouts.admin>

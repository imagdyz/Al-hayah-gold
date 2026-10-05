<x-layouts.site title="بيع ذهبك أو بدّله" description="احسب قيمة ذهبك القديم بسعر اللحظة واحجز معاد في أقرب فرع للحياة جولد.">
    <x-page-head title="بيع ذهبك أو بدّله" lead="احسب قيمة ذهبك التقريبية واحجز معاد. في الفرع بيتوزن ويتفحص قدامك، وتاخد فلوسك أو قطعة جديدة." />

    <div class="container-site grid gap-8 py-10 lg:grid-cols-[1fr_1fr]" x-data="sellEstimate(@js($buy), { karat: '{{ old('karat', '21') }}', grams: {{ (float) old('grams', 10) }} })">
        <section class="card-ink relative flex flex-col gap-5 overflow-hidden p-7 sm:p-9" aria-labelledby="est">
            <div class="star-pattern absolute inset-0 opacity-[.05]" aria-hidden="true"></div>
            <h2 id="est" class="relative m-0 font-display text-[28px]">قيمة ذهبك النهارده</h2>
            <div class="relative flex gap-1 rounded-full border border-gold-bright/15 bg-white/5 p-1" role="group" aria-label="العيار">
                @foreach (['24', '21', '18'] as $k)
                    <button type="button" class="min-h-11 flex-1 rounded-full text-sm font-semibold" :class="karat === '{{ $k }}' ? 'bg-gradient-to-b from-[#EACD80] to-gold text-ink' : 'text-[#CFC6B4]'" :aria-pressed="karat === '{{ $k }}'" @click="karat = '{{ $k }}'">عيار {{ $k }}</button>
                @endforeach
            </div>
            <label for="grams" class="relative text-[15px] text-[#CFC6B4]">الوزن التقريبي (جرام)</label>
            <input id="grams" type="number" inputmode="decimal" min="0" step="0.5" x-model.number="grams" dir="ltr" class="field field-ink relative min-h-[68px] text-right text-[34px] font-bold">
            <div class="relative flex flex-wrap gap-2">
                @foreach ([5, 10, 20, 50] as $g)
                    <button type="button" class="btn btn-sm btn-ghost-ink min-h-10 font-medium" @click="grams = {{ $g }}">{{ $g }} جم</button>
                @endforeach
            </div>
            <div class="relative flex flex-col gap-1.5 rounded-[18px] border border-[#2E271D] bg-ink-2 p-5" aria-live="polite">
                <span class="text-sm text-muted-dark">قيمته التقريبية</span>
                <b class="text-gold-grad font-display text-[40px] leading-tight">≈ <span x-text="$fmt(value)"></span> ج.م</b>
                <span class="text-sm text-[#CFC6B4]"><span x-text="grams || 0"></span> جم × <span x-text="$fmt(buy[karat])"></span> ج.م (سعر شرائنا لعيار <span x-text="karat"></span>)</span>
            </div>
            <p class="relative m-0 flex gap-2 text-[13px] leading-relaxed text-muted-dark"><x-icon name="info" :size="18" class="mt-0.5 text-gold" />القيمة النهائية بعد الوزن والفحص في الفرع، بسعر لحظة البيع. هات الفاتورة لو معاك.</p>
        </section>

        <form id="book" method="POST" action="{{ route('sell.store') }}" class="card flex flex-col gap-6 p-7 sm:p-9">
            @csrf
            <input type="hidden" name="karat" :value="karat">
            <input type="hidden" name="grams" :value="grams">
            <h2 class="m-0 font-display text-[28px]">احجز معاد في الفرع</h2>
            <x-flash />
            @error('grams')<p class="error">{{ $message }}</p>@enderror
            <div>
                <label for="branch" class="label">الفرع</label>
                <select id="branch" name="branch_id" class="field">
                    @foreach ($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->fullName() }} · {{ $b->hoursLabel() }}</option>@endforeach
                </select>
                @error('branch_id')<p class="error">{{ $message }}</p>@enderror
            </div>
            <fieldset class="m-0 flex flex-col gap-2 border-0 p-0">
                <legend class="label">المعاد</legend>
                <div class="grid gap-2.5 [grid-template-columns:repeat(auto-fill,minmax(140px,1fr))]">
                    @foreach ($slots as $value => $label)
                        <label class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border border-line bg-white px-3 text-sm font-semibold has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-paper">
                            <input type="radio" name="slot" value="{{ $value }}" class="sr-only" @checked(old('slot', array_key_first($slots)) === $value)>{{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('slot')<p class="error">{{ $message }}</p>@enderror
            </fieldset>
            <div>
                <label for="notes" class="label">ملاحظات <span class="font-normal text-muted">(اختياري)</span></label>
                <textarea id="notes" name="notes" rows="3" class="field py-3" placeholder="مثلاً: عايز أبدّله بخاتم، أو معايا فاتورة">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-lg btn-ink">@auth احجز المعاد @else سجّل دخول واحجز المعاد @endauth</button>
        </form>
    </div>
</x-layouts.site>

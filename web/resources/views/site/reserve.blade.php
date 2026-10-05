@php $stock = $product->branches->mapWithKeys(fn ($b) => [$b->id => (int) $b->pivot->quantity]); @endphp
<x-layouts.site :title="'احجز '.$product->name">
    <x-page-head :title="'احجز '.$product->name" :crumbs="['المجوهرات' => route('shop.index'), $product->name => route('shop.show', $product)]"
                 :lead="'القطعة بتتحجز باسمك '.$holdHours.' ساعة. تشوفها في الفرع، ولو عجبتك تدفع وتستلمها.'" />

    <form method="POST" action="{{ route('reserve.store', $product) }}" class="container-site grid gap-8 py-10 lg:grid-cols-[1fr_380px]" x-data="{ pay: '{{ old('pay_method', 'branch') }}' }">
        @csrf
        <div class="flex min-w-0 flex-col gap-8">
            <x-flash />
            <fieldset class="m-0 flex flex-col gap-3 border-0 p-0">
                <legend class="mb-3 font-display text-2xl font-bold">في أنهي فرع؟</legend>
                @php $firstStocked = $branches->first(fn ($b) => ($stock[$b->id] ?? 0) > 0); @endphp
                @foreach ($branches as $branch)
                    @php $qty = $stock[$branch->id] ?? 0; @endphp
                    <label class="option">
                        <span class="flex flex-col"><b>{{ $branch->fullName() }}</b><span class="text-[13px] {{ $qty ? 'text-up' : 'text-muted' }}">{{ $qty ? 'متوفرة · '.$qty.' '.($qty === 1 ? 'قطعة' : 'قطع') : 'مش موجودة في الفرع ده دلوقتي' }}</span></span>
                        <input type="radio" name="branch_id" value="{{ $branch->id }}" @disabled(! $qty) @checked(old('branch_id', $firstStocked?->id) == $branch->id)>
                    </label>
                @endforeach
                @error('branch_id')<p class="error">{{ $message }}</p>@enderror
            </fieldset>

            <fieldset class="m-0 flex flex-col gap-3 border-0 p-0">
                <legend class="mb-3 font-display text-2xl font-bold">إمتى هتعدّي؟</legend>
                <div class="grid gap-2.5 [grid-template-columns:repeat(auto-fill,minmax(150px,1fr))]">
                    @foreach ($slots as $value => $label)
                        <label class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border border-line bg-white px-3 text-sm font-semibold has-[:checked]:border-gold has-[:checked]:bg-gold has-[:checked]:text-ink">
                            <input type="radio" name="slot" value="{{ $value }}" class="sr-only" @checked(old('slot', array_key_first($slots)) === $value)>{{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('slot')<p class="error">{{ $message }}</p>@enderror
            </fieldset>

            <fieldset class="m-0 flex flex-col gap-3 border-0 p-0">
                <legend class="mb-3 font-display text-2xl font-bold">الدفع</legend>
                <label class="option"><span class="flex flex-col"><b>ادفع كامل في الفرع</b><span class="text-[13px] text-muted">كاش أو كارت بعد ما تشوف القطعة</span></span><input type="radio" name="pay_method" value="branch" x-model="pay"></label>
                <label class="option"><span class="flex flex-col"><b>عربون {{ $depositPercent }}% والباقي في الفرع</b><span class="text-[13px] text-muted">بيثبّت السعر ويضمن القطعة ليك</span></span><input type="radio" name="pay_method" value="deposit" x-model="pay"></label>
            </fieldset>
        </div>

        <aside class="flex flex-col gap-4 lg:sticky lg:top-6 lg:self-start">
            <div class="card flex flex-col gap-4 p-5">
                <div class="flex items-center gap-4">
                    <span class="pedestal flex size-24 flex-none items-center justify-center rounded-2xl"><img src="{{ $product->imageSrc() }}" alt="" class="size-20 object-contain"></span>
                    <span class="flex flex-col gap-1"><b class="font-display text-xl">{{ $product->name }}</b><span class="text-sm text-muted">{{ $product->karatLabel() }} · {{ $product->weightLabel() }}</span></span>
                </div>
                <dl class="m-0 flex flex-col gap-2.5 border-t border-line pt-4 text-[15px]">
                    <div class="flex justify-between"><dt class="text-muted">قيمة الذهب</dt><dd class="m-0 font-bold">{{ number_format($product->goldValue()) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">المصنعية</dt><dd class="m-0 font-bold">{{ number_format((float) $product->making_fee) }}</dd></div>
                    <div class="flex justify-between" x-show="pay === 'deposit'" x-cloak><dt class="text-muted">العربون</dt><dd class="m-0 font-bold">{{ number_format(round($product->price() * $depositPercent / 100)) }}</dd></div>
                    <div class="flex items-baseline justify-between border-t border-line pt-3"><dt class="font-bold">السعر النهارده</dt><dd class="text-gold-grad-deep m-0 font-display text-3xl font-bold">≈ {{ number_format($product->price()) }} ج.م</dd></div>
                </dl>
                <button type="submit" class="btn btn-lg btn-gold w-full" @disabled($prices['halted'])>تأكيد الحجز</button>
                <p class="m-0 text-center text-xs text-muted">مفيش أي رسوم على الحجز. لو ما عجبتكش القطعة متدفعش حاجة.</p>
            </div>
        </aside>
    </form>
</x-layouts.site>

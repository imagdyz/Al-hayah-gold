<x-layouts.site title="سبائك وجنيهات ذهب" description="اطلب سبيكة ذهب عيار 24 أو جنيه ذهب بسعر اللحظة واستلمه من أقرب فرع للحياة جولد.">
    <x-page-head title="سبائك وجنيهات" lead="اختار الوزن والفرع، وسعرك بيتثبّت {{ $lockMinutes }} دقيقة من التأكيد. تدفع في الفرع أو بعربون، وتستلم بكود الطلب." />

    @php
        $itemsJs = $items->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'img' => $p->imageSrc(), 'price' => $p->price(),
            'spec' => $p->category === \App\Enums\Category::Coin ? '8 جم · عيار 21' : 'عيار 24 · 999.9',
            'stock' => $p->branches->mapWithKeys(fn ($b) => [$b->id => $b->pivot->quantity]),
        ])->values();
    @endphp

    <form method="POST" action="{{ route('bullion.store') }}" class="container-site grid gap-8 py-10 lg:grid-cols-[1fr_400px]"
          x-data="bullionOrder(@js($itemsJs), { id: {{ old('product_id', $selected?->id ?? 0) }}, qty: {{ (int) old('quantity', 1) }}, pay: '{{ old('pay_method', 'branch') }}', depositPercent: {{ $depositPercent }} })">
        @csrf
        <div class="flex min-w-0 flex-col gap-7">
            <x-flash />
            <div class="pedestal-ink relative flex h-[300px] items-center justify-center overflow-hidden rounded-[28px] sm:h-[360px]">
                <div class="star-pattern absolute inset-0 opacity-[.06]" aria-hidden="true"></div>
                <img :src="item.img" :alt="item.name + ' (صورة توضيحية)'" class="relative h-[86%] object-contain" src="{{ $selected?->imageSrc() }}" alt="">
                <span class="pill absolute start-4 top-4 border border-gold-bright/35 text-gold-bright" x-text="item.spec"></span>
                <span class="absolute bottom-3 end-4 text-xs text-[#7D7466]">صورة توضيحية</span>
            </div>

            <fieldset class="m-0 flex flex-col gap-3 border-0 p-0">
                <legend class="mb-3 font-display text-2xl font-bold">اختار الوزن</legend>
                <div class="grid gap-3 [grid-template-columns:repeat(auto-fill,minmax(128px,1fr))]">
                    @foreach ($items as $item)
                        <label class="lift flex cursor-pointer flex-col items-center gap-1 rounded-2xl border bg-white p-3 text-center has-[:checked]:border-2 has-[:checked]:border-gold has-[:checked]:shadow-[0_8px_18px_-12px_rgba(168,133,58,.9)] border-line">
                            <input type="radio" name="product_id" value="{{ $item->id }}" x-model.number="id" class="sr-only">
                            <img src="{{ $item->imageSrc() }}" alt="" class="size-16 object-contain">
                            <b class="text-sm">{{ $item->category === \App\Enums\Category::Coin ? 'جنيه ذهب' : $item->weightLabel() }}</b>
                            <span class="text-xs font-bold text-gold-dark">{{ number_format($item->price()) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('product_id')<p class="error">{{ $message }}</p>@enderror
            </fieldset>

            <fieldset class="m-0 flex flex-col gap-3 border-0 p-0">
                <legend class="mb-3 font-display text-2xl font-bold">فرع الاستلام</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($branches as $branch)
                        <label class="option">
                            <span class="flex flex-col"><b class="text-[15px]">{{ $branch->fullName() }}</b>
                                <span class="text-[13px] text-muted" x-text="(item.stock[{{ $branch->id }}] ?? 0) >= qty ? 'متوفر · جاهز خلال ساعة' : 'مش متوفر بالكمية دي'"></span></span>
                            <input type="radio" name="branch_id" value="{{ $branch->id }}" @checked(old('branch_id', $branches->first()->id) == $branch->id) :disabled="(item.stock[{{ $branch->id }}] ?? 0) < qty">
                        </label>
                    @endforeach
                </div>
                @error('branch_id')<p class="error">{{ $message }}</p>@enderror
            </fieldset>
        </div>

        <aside class="flex flex-col gap-4 lg:sticky lg:top-6 lg:self-start">
            <div class="card flex flex-col gap-4 p-6">
                <div class="flex items-center justify-between">
                    <span class="flex flex-col"><b class="text-[15px]">الكمية</b><span class="text-[13px] text-muted" x-text="item.name"></span></span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="size-11 rounded-xl bg-ink text-xl text-gold-bright" @click="inc()" aria-label="زوّد">+</button>
                        <input type="number" name="quantity" x-model.number="qty" min="1" max="20" class="w-12 border-0 bg-transparent text-center text-xl font-bold" aria-label="الكمية" readonly>
                        <button type="button" class="size-11 rounded-xl border border-line-strong bg-white text-xl" @click="dec()" aria-label="قلّل">−</button>
                    </div>
                </div>
                <fieldset class="m-0 grid grid-cols-2 gap-2 border-0 p-0">
                    <legend class="mb-2 text-sm font-semibold">الدفع</legend>
                    <label class="flex min-h-16 cursor-pointer flex-col justify-center gap-0.5 rounded-2xl border border-line px-3.5 py-3 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-paper">
                        <input type="radio" name="pay_method" value="branch" x-model="pay" class="sr-only"><b class="text-sm">ادفع في الفرع</b><span class="text-xs opacity-80">كاش أو كارت عند الاستلام</span>
                    </label>
                    <label class="flex min-h-16 cursor-pointer flex-col justify-center gap-0.5 rounded-2xl border border-line px-3.5 py-3 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-paper">
                        <input type="radio" name="pay_method" value="deposit" x-model="pay" class="sr-only"><b class="text-sm">عربون أونلاين</b><span class="text-xs opacity-80">{{ $depositPercent }}% والباقي في الفرع</span>
                    </label>
                </fieldset>
                <div class="flex items-center gap-2.5 rounded-2xl bg-warn-bg px-3.5 py-3 text-sm text-warn"><x-icon name="lock" :size="18" />السعر بيتثبّت لك {{ $lockMinutes }} دقيقة من التأكيد</div>
                <dl class="m-0 flex flex-col gap-2.5 border-t border-line pt-4 text-[15px]">
                    <div class="flex justify-between"><dt class="text-muted" x-text="item.name + ' × ' + qty"></dt><dd class="m-0 font-bold" x-text="$fmt(total) + ' ج.م'"></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">المصنعية والدمغة</dt><dd class="m-0 font-bold">داخلة في السعر</dd></div>
                    <div class="flex justify-between" x-show="pay === 'deposit'" x-cloak><dt class="text-muted">العربون دلوقتي</dt><dd class="m-0 font-bold" x-text="$fmt(deposit) + ' ج.م'"></dd></div>
                    <div class="flex items-baseline justify-between border-t border-line pt-3"><dt class="font-bold">الإجمالي</dt><dd class="text-gold-grad-deep m-0 font-display text-3xl font-bold" x-text="$fmt(total) + ' ج.م'"></dd></div>
                </dl>
                <button type="submit" class="btn btn-lg btn-gold w-full" @disabled($prices['halted'])>
                    @auth <span x-text="pay === 'branch' ? 'ثبّت السعر واطلب' : 'ادفع العربون واطلب'">ثبّت السعر واطلب</span> @else سجّل دخول وكمّل الطلب @endauth
                </button>
                <p class="m-0 text-center text-xs text-muted">أسعار توضيحية · السعر النهائي وقت التأكيد</p>
            </div>
        </aside>
    </form>
</x-layouts.site>

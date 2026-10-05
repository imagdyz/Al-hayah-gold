@php
    $pricing = app(\App\Services\GoldPricing::class);
    $karatKey = $product->category === \App\Enums\Category::Coin ? 'coin' : (string) $product->karat;
    $stocked = $product->stockedBranches();
@endphp
<x-layouts.site :title="$product->name" :description="$product->name.' '.$product->karatLabel().' · '.$product->weightLabel().' · متوفر في فروع الحياة جولد'">
    <div class="container-site flex flex-col gap-10 pb-20 pt-6">
        <nav aria-label="مسار الصفحة" class="flex flex-wrap gap-2 text-sm text-muted">
            <a href="{{ route('home') }}" class="text-gold-dark no-underline">الرئيسية</a><span aria-hidden="true">/</span>
            @if ($product->isBullion())
                <a href="{{ route('bullion') }}" class="text-gold-dark no-underline">سبائك وجنيهات</a>
            @else
                <a href="{{ route('shop.index') }}" class="text-gold-dark no-underline">المجوهرات</a>
            @endif
            <span aria-hidden="true">/</span><span>{{ $product->name }}</span>
        </nav>

        <div class="flex flex-wrap items-start gap-12">
            <div class="flex min-w-0 flex-[1_1_480px] flex-col gap-3.5" x-data="gallery(@js($product->gallerySrcs()))">
                <div class="pedestal relative flex aspect-square items-center justify-center overflow-hidden rounded-[32px] shadow-[inset_0_0_0_1px_#E6DECF]">
                    <img :src="images[i]" src="{{ $product->imageSrc() }}" alt="{{ $product->name }} {{ $product->karatLabel() }} (صورة توضيحية)" class="h-[84%] w-[84%] object-contain">
                    <span class="absolute bottom-4 start-4 rounded-full bg-white/90 px-3 py-1.5 text-xs text-muted">صورة توضيحية · [تصوير القطعة من المحل]</span>
                </div>
                <div class="grid grid-cols-4 gap-3" x-show="images.length > 1">
                    <template x-for="(src, n) in images" :key="n">
                        <button type="button" class="pedestal flex aspect-square items-center justify-center rounded-[18px] border" :class="n === i ? 'border-2 border-ink' : 'border-line'" @click="i = n" :aria-label="'صورة ' + (n + 1)">
                            <img :src="src" alt="" class="h-[78%] w-[78%] object-contain">
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex min-w-0 flex-[1_1_400px] flex-col gap-5">
                @if ($stocked->isNotEmpty())
                    <span class="pill pill-green self-start"><span class="size-1.5 rounded-full bg-up"></span>متوفرة في {{ $stocked->count() === 1 ? 'فرع واحد' : $stocked->count().' من فروعنا' }}</span>
                @else
                    <span class="pill pill-amber self-start">بالطلب من الفروع</span>
                @endif
                <div class="flex flex-col gap-2.5">
                    <h1 class="m-0 font-display text-[2.4rem] font-bold leading-tight sm:text-[2.9rem]">{{ $product->name }}</h1>
                    @if ($product->description)<p class="m-0 leading-[1.8] text-muted">{{ $product->description }}</p>@endif
                </div>
                <dl class="m-0 grid grid-cols-2 overflow-hidden rounded-[20px] border border-line bg-white sm:grid-cols-4">
                    @foreach (['العيار' => $product->karat, 'الوزن' => $product->weightLabel(), 'التفاصيل' => $product->subtitle ?: '—', 'الكود' => $product->sku ?: '—'] as $dt => $dd)
                        <div class="flex flex-col gap-1 border-line px-3 py-3.5 text-center [&:not(:first-child)]:border-s"><dt class="text-xs text-muted">{{ $dt }}</dt><dd class="m-0 font-bold">{{ $dd }}</dd></div>
                    @endforeach
                </dl>

                <div class="card flex flex-col gap-3 p-[22px] text-[15px] shadow-raised">
                    <div class="flex justify-between gap-3"><span class="text-muted">قيمة الذهب
                        @if ($karatKey !== 'coin') ({{ $product->weightLabel() }} × {{ number_format($pricing->sell($karatKey)) }}) @endif</span><b>{{ number_format($product->goldValue()) }} ج.م</b></div>
                    <div class="flex justify-between gap-3"><span class="text-muted">المصنعية</span><b>{{ number_format((float) $product->making_fee) }} ج.م</b></div>
                    <div class="h-px bg-line"></div>
                    <div class="flex items-baseline justify-between gap-3"><b class="text-[17px]">السعر النهارده</b><b class="text-gold-grad-deep font-display text-4xl">≈ {{ number_format($product->price()) }} ج.م</b></div>
                    <span class="text-[13px] text-muted">بيتحسب بسعر الذهب وقت الحجز · آخر تحديث {{ optional($pricing->updatedAt())->locale('ar')->diffForHumans() }}</span>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($product->isBullion())
                        <a href="{{ route('bullion', ['item' => $product->slug]) }}" class="btn btn-lg btn-gold flex-[1_1_240px]">اطلبها واستلم من الفرع</a>
                    @else
                        <a href="{{ route('reserve.create', $product) }}" class="btn btn-lg btn-gold flex-[1_1_240px]" @if ($stocked->isEmpty()) aria-disabled="true" @endif>احجزها في الفرع</a>
                    @endif
                    <a href="{{ route('branches') }}" class="btn btn-lg btn-outline flex-[1_1_160px]"><x-icon name="phone" :size="18" />كلّم الفرع</a>
                </div>
                @unless ($product->isBullion())
                    <span class="flex items-center gap-2 text-sm text-muted"><x-icon name="lock" :size="18" class="text-gold-dark" />الحجز مجاني لمدة {{ \App\Models\Setting::int('reservation_hours') }} ساعة، وتدفع في الفرع بعد ما تشوفها.</span>
                    <a href="{{ route('sell') }}" class="flex items-center gap-3.5 rounded-[22px] p-[18px] text-text no-underline" style="background: linear-gradient(120deg, #F1E6CC 0%, #EADBB6 100%)">
                        <span class="flex size-[46px] flex-none items-center justify-center rounded-[14px] bg-ink text-gold-bright"><x-icon name="swap" :size="22" /></span>
                        <span class="flex flex-col gap-1 leading-relaxed"><b>عندك ذهب قديم؟</b><span class="text-sm text-[#3F3524]">هاته معاك الفرع، يتوزن قدامك ويتخصم من تمن القطعة بسعر اللحظة.</span></span>
                    </a>
                @endunless

                <div class="flex flex-col gap-2.5">
                    <b class="text-[17px]">التوفر في الفروع</b>
                    <div class="overflow-hidden rounded-[18px] border border-line bg-white">
                        @foreach ($branches as $branch)
                            @php $qty = (int) optional($product->branches->firstWhere('id', $branch->id))->pivot?->quantity; @endphp
                            <div class="flex items-center justify-between gap-3 px-[18px] py-3.5 [&:not(:first-child)]:border-t [&:not(:first-child)]:border-[#F0EADF]">
                                <span><b>{{ $branch->name }}</b> — {{ $branch->area }}</span>
                                @if ($qty > 0)
                                    <span class="pill pill-green">متوفر · {{ $qty === 1 ? 'قطعة واحدة' : ($qty === 2 ? 'قطعتين' : $qty.' قطع') }}</span>
                                @else
                                    <span class="pill pill-amber">تحويل خلال [مدة]</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="flex flex-col gap-5">
                <div class="flex items-end justify-between gap-3"><h2 class="m-0 font-display text-[2rem]">قطع ممكن تعجبك</h2><a href="{{ route('shop.index') }}" class="py-2.5 font-bold no-underline">كل المجوهرات ←</a></div>
                <div class="grid gap-6 [grid-template-columns:repeat(auto-fill,minmax(min(240px,100%),1fr))]">
                    @foreach ($related as $r)
                        <x-product-card :product="$r" ratio="1 / 1" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.site>

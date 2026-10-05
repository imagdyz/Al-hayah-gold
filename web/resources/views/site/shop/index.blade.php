<x-layouts.site title="المجوهرات" description="مجوهرات ذهب عيار 21 و18 موجودة فعلاً في فروع الحياة جولد. شوف القطعة في أنهي فرع واحجزها.">
    <x-page-head title="المجوهرات" :lead="$products->count().' قطعة موجودة دلوقتي في فروعنا · المخزون بيتحدث من الفروع مباشرة'">
        <form method="GET" class="flex items-center gap-2.5" x-data>
            @foreach (request()->except(['sort', 'page']) as $key => $value)
                @if (is_array($value)) @foreach ($value as $v)<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endforeach
                @else <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endif
            @endforeach
            <label for="sort" class="text-sm text-muted">ترتيب حسب</label>
            <select id="sort" name="sort" class="field w-auto min-h-[46px]" @change="$el.form.submit()">
                @foreach (['new' => 'الأحدث', 'price_asc' => 'السعر: من الأقل', 'price_desc' => 'السعر: من الأعلى', 'weight' => 'الوزن'] as $v => $l)
                    <option value="{{ $v }}" @selected(($filters['sort'] ?? 'new') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </form>
    </x-page-head>

    <div class="container-site flex flex-wrap items-start gap-8 py-8 pb-20">
        <aside aria-label="الفلاتر" class="card w-full flex-[1_1_250px] lg:max-w-[290px]" x-data="{ open: false }">
            <button type="button" class="flex min-h-14 w-full items-center justify-between px-[22px] font-bold lg:hidden" @click="open = !open" :aria-expanded="open">
                <span class="flex items-center gap-2"><x-icon name="search" :size="18" />تصفية وبحث</span><x-icon name="chevron-down" ::class="open && 'rotate-180'" />
            </button>
            <form method="GET" class="flex flex-col gap-5 p-[22px] max-lg:pt-0" :class="open ? '' : 'max-lg:hidden'">
                <div class="flex items-center justify-between"><b class="font-display text-xl">تصفية</b><a href="{{ route('shop.index') }}" class="text-sm text-gold-dark">مسح الكل</a></div>
                <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'new' }}">
                <div class="flex flex-col gap-2.5">
                    <label for="q" class="text-[15px] font-bold">بحث</label>
                    <div class="relative"><x-icon name="search" :size="18" class="absolute start-3 top-1/2 -translate-y-1/2 text-muted" /><input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="خاتم، سلسلة، دبلة…" class="field ps-10"></div>
                </div>
                <fieldset class="m-0 flex flex-col gap-2 border-0 border-t border-line p-0 pt-4">
                    <legend class="float-right mb-2 w-full text-[15px] font-bold">النوع</legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="chip min-h-10 text-sm has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-paper"><input type="radio" name="c" value="" class="sr-only" @checked(! $cat)>الكل</label>
                        @foreach (\App\Enums\Category::filters() as $slug => $label)
                            <label class="chip min-h-10 text-sm has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-paper"><input type="radio" name="c" value="{{ $slug }}" class="sr-only" @checked($cat === $slug)>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset class="m-0 flex flex-col gap-1 border-0 border-t border-line p-0 pt-4">
                    <legend class="float-right mb-2 w-full text-[15px] font-bold">العيار</legend>
                    @foreach ([24, 21, 18] as $k)
                        <label class="flex min-h-[38px] items-center gap-2.5"><input type="checkbox" name="karat[]" value="{{ $k }}" class="size-[18px] accent-gold-deep" @checked(in_array((string) $k, $filters['karat'] ?? [], true))>عيار {{ $k }}</label>
                    @endforeach
                </fieldset>
                <fieldset class="m-0 flex flex-col gap-1 border-0 border-t border-line p-0 pt-4">
                    <legend class="float-right mb-2 w-full text-[15px] font-bold">متوفر في</legend>
                    <label class="flex min-h-[38px] items-center gap-2.5"><input type="radio" name="branch" value="" class="size-[18px] accent-gold-deep" @checked(empty($filters['branch']))>كل الفروع</label>
                    @foreach ($branches as $b)
                        <label class="flex min-h-[38px] items-center gap-2.5"><input type="radio" name="branch" value="{{ $b->id }}" class="size-[18px] accent-gold-deep" @checked(($filters['branch'] ?? null) == $b->id)>{{ $b->fullName() }}</label>
                    @endforeach
                </fieldset>
                <div class="flex flex-col gap-2 border-t border-line pt-4"><b class="text-[15px]">الوزن (جم)</b>
                    <div class="flex gap-2">
                        <label class="flex flex-1 flex-col gap-1 text-[13px] text-muted">من<input type="number" name="min" step="0.5" min="0" value="{{ $filters['min'] ?? '' }}" class="field"></label>
                        <label class="flex flex-1 flex-col gap-1 text-[13px] text-muted">إلى<input type="number" name="max" step="0.5" min="0" value="{{ $filters['max'] ?? '' }}" class="field"></label>
                    </div>
                </div>
                <button class="btn btn-ink w-full">اعرض النتايج</button>
                <div class="flex flex-col gap-2 rounded-[18px] bg-ink p-4 text-sm leading-relaxed text-on-ink">
                    <b class="text-gold-bright">مش لاقي اللي في بالك؟</b>
                    <span>ابعت لنا صورة القطعة، والفروع تدوّر لك عليها أو تتصنع لك.</span>
                    <a href="{{ route('branches') }}" class="font-semibold text-gold-bright">كلّم أقرب فرع ←</a>
                </div>
            </form>
        </aside>

        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-8">
            @if ($products->isEmpty())
                <div class="card flex flex-col items-center gap-3 p-12 text-center">
                    <x-icon name="search" :size="32" class="text-gold" />
                    <b class="font-display text-2xl">مفيش قطع بالمواصفات دي دلوقتي</b>
                    <p class="m-0 text-muted">جرّب تشيل فلتر أو تختار فرع تاني.</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-outline">اعرض كل المجوهرات</a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-3 gap-y-7 sm:gap-x-5 sm:[grid-template-columns:repeat(auto-fill,minmax(240px,1fr))]">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.site>

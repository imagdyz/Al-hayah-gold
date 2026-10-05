<x-layouts.site>
    {{-- Hero --}}
    <section class="relative overflow-hidden text-[#F4F2EE]" style="background: radial-gradient(1100px 620px at 22% 46%, #3A2E1A 0%, #1C1812 48%, #12100C 100%)">
        <div class="star-pattern pointer-events-none absolute inset-0 opacity-[.07]" aria-hidden="true"></div>
        <div class="container-site relative flex flex-wrap items-center gap-x-14 gap-y-10 pb-16 pt-14 lg:pt-[72px]">
            <div class="flex min-w-0 flex-[1_1_440px] flex-col gap-6">
                <span class="inline-flex animate-rise items-center gap-2.5 self-start text-sm font-semibold tracking-wider text-gold-bright"><span class="inline-block h-px w-7 bg-gold"></span>سبائك · جنيهات · مجوهرات</span>
                <h1 class="m-0 animate-rise font-display text-[2.6rem] font-bold leading-[1.18] [animation-delay:.08s] sm:text-[3.4rem] xl:text-[4rem]">اشترِ ذهبك بسعر اللحظة <span class="text-gold-grad">واستلمه من أقرب فرع</span></h1>
                <p class="m-0 max-w-[560px] animate-rise text-lg leading-[1.85] text-[#CFC6B4] [animation-delay:.16s]">سبائك وجنيهات بختم الحياة جولد بسعر بيتثبّت وقت الطلب، ومجوهرات موجودة فعلاً في محلاتنا. شوفها أونلاين، اعرف هي في أنهي فرع، واحجزها قبل ما تنزل.</p>
                <div class="flex animate-rise flex-wrap gap-3 [animation-delay:.24s]">
                    <a href="{{ route('bullion') }}" class="btn btn-lg btn-gold">اطلب سبيكة <x-icon name="arrow" :size="18" :stroke="2" /></a>
                    <a href="{{ route('shop.index') }}" class="btn btn-lg btn-ghost-ink">تصفّح المجوهرات</a>
                </div>
                <div class="flex animate-rise flex-wrap gap-x-7 gap-y-3 border-t border-[#2E271D] pt-5 text-[15px] text-[#CFC6B4] [animation-delay:.32s]">
                    <span class="flex items-center gap-2"><x-icon name="shield" class="text-gold" />سبائك مختومة 999.9</span>
                    <span class="flex items-center gap-2"><x-icon name="receipt" class="text-gold" />فاتورة وضمان من الفرع</span>
                    <span class="flex items-center gap-2"><x-icon name="pin" class="text-gold" />استلام من {{ $branches->count() }} فروع</span>
                </div>
            </div>

            <div class="relative flex min-h-[420px] min-w-0 flex-[1_1_480px] items-center justify-center">
                <div class="absolute inset-[8%_6%_10%] rounded-full blur-sm" style="background: radial-gradient(closest-side, rgba(230,199,122,.32), rgba(230,199,122,0))" aria-hidden="true"></div>
                <img src="{{ asset('images/products/hero.webp') }}" alt="سبائك وجنيهات ذهب بختم الحياة جولد (صورة توضيحية)" class="relative w-full max-w-[640px] animate-float" fetchpriority="high">

                <div class="absolute -bottom-8 start-0 flex w-[280px] max-w-[82%] flex-col gap-2.5 rounded-[20px] border border-gold-bright/20 bg-ink-2/75 p-5 shadow-float backdrop-blur-xl" x-data>
                    <div class="flex items-center justify-between text-[13px] text-muted-dark">
                        <span class="inline-flex items-center gap-2"><span class="live-dot"></span>عيار 21 · الآن</span>
                        <span class="font-semibold" :class="($store.prices.quotes['21']?.change ?? 0) >= 0 ? 'text-up-on-ink' : 'text-[#F0A39A]'"
                              x-text="(($store.prices.quotes['21']?.change ?? 0) >= 0 ? '▲ ' : '▼ ') + Math.abs($store.prices.quotes['21']?.change ?? 0) + '%'">▲ {{ $prices['quotes']['21']['change'] }}%</span>
                    </div>
                    <div class="-mx-1.5 flex items-baseline gap-2 rounded-lg px-1.5" :class="$store.prices.flash['21']">
                        <b class="text-4xl font-bold tracking-tight text-white" x-text="$store.prices.sell('21')">{{ number_format($prices['quotes']['21']['sell']) }}</b>
                        <span class="text-sm text-gold">ج.م / جرام</span>
                    </div>
                    <x-sparkline :values="$sparks['21']" :height="46" stroke="#E6C77A" fill="rgba(200,160,75,.18)" />
                    <span class="text-xs text-[#7D7466]">نبيع <span x-text="$store.prices.sell('21')">{{ number_format($prices['quotes']['21']['sell']) }}</span> · نشتري <span x-text="$store.prices.buy('21')">{{ number_format($prices['quotes']['21']['buy']) }}</span> · آخر 24 ساعة</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Prices --}}
    <section id="prices" class="container-site flex flex-col gap-8 pb-12 pt-20" x-data>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex max-w-[640px] flex-col gap-2.5">
                <span class="eyebrow">أسعار اليوم</span>
                <h2 class="m-0 font-display text-[2.1rem] font-bold leading-tight sm:text-[2.75rem]">سعر واحد في الموقع والتطبيق وكل الفروع</h2>
                <p class="m-0 text-[17px] leading-relaxed text-muted">السعر بيتحدث كل دقيقة حسب البورصة العالمية وسعر الصرف، وبيتثبّت لحظة ما تأكد طلبك.</p>
            </div>
            <a href="{{ route('prices') }}" class="inline-flex items-center gap-2 py-2.5 font-bold no-underline">الرسم البياني والتفاصيل <x-icon name="chevron" :size="18" /></a>
        </div>
        <div class="grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr))]">
            @foreach ($prices['quotes'] as $k => $q)
                @php $dark = $k === 'coin'; @endphp
                <div class="lift flex flex-col gap-3.5 p-6 {{ $dark ? 'card-ink' : 'card' }}">
                    <div class="flex items-center justify-between">
                        <b class="font-display text-[22px]">{{ $q['label'] }}</b>
                        <span class="pill {{ $dark ? 'bg-up-on-ink/15 text-up-on-ink' : 'pill-green' }}">{{ $q['change'] >= 0 ? '▲' : '▼' }} {{ abs($q['change']) }}%</span>
                    </div>
                    <x-sparkline :values="$sparks[$k]" :stroke="$dark ? '#E6C77A' : '#A8853A'" :fill="$dark ? 'rgba(230,199,122,.14)' : 'rgba(200,160,75,.12)'" />
                    <div class="grid grid-cols-2 gap-3">
                        <span class="flex flex-col gap-0.5"><span class="text-[13px] {{ $dark ? 'text-muted-dark' : 'text-muted' }}">نبيع لك</span>
                            <b class="rounded px-0.5 text-[26px] font-bold {{ $dark ? 'text-gold-bright' : 'text-ink' }}" :class="$store.prices.flash['{{ $k }}']" x-text="$store.prices.sell('{{ $k }}')">{{ number_format($q['sell']) }}</b></span>
                        <span class="flex flex-col gap-0.5"><span class="text-[13px] {{ $dark ? 'text-muted-dark' : 'text-muted' }}">نشتري منك</span>
                            <b class="text-[26px] font-semibold" x-text="$store.prices.buy('{{ $k }}')">{{ number_format($q['buy']) }}</b></span>
                    </div>
                    <span class="text-[13px] {{ $dark ? 'text-muted-dark' : 'text-muted' }}">{{ $q['unit'] }}{{ $k === '24' ? ' · سبائك 999.9' : ($k === '21' ? ' · الأكثر طلباً في المشغولات' : ($k === '18' ? ' · مجوهرات وألماظ' : ' · 8 جرام عيار 21')) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Bullion --}}
    <section id="bullion" class="container-site flex flex-col gap-8 pb-20 pt-12">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex max-w-[640px] flex-col gap-2.5">
                <span class="eyebrow">سبائك وجنيهات</span>
                <h2 class="m-0 font-display text-[2.1rem] font-bold leading-tight sm:text-[2.75rem]">اطلب أونلاين، واستلم مختومة من الفرع</h2>
                <p class="m-0 text-[17px] leading-relaxed text-muted">اختار الوزن والفرع، وسعرك بيتثبّت {{ \App\Models\Setting::int('price_lock_minutes') }} دقيقة. تدفع في الفرع أو بعربون، وتستلم بكود الطلب.</p>
            </div>
            <a href="#calc" class="inline-flex items-center gap-2 py-2.5 font-bold no-underline">احسب ميزانيتك <x-icon name="chevron" :size="18" /></a>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            @foreach ($bullion as $b)
                <a href="{{ route('bullion', ['item' => $b->slug]) }}" class="lift card flex flex-col gap-2.5 p-3.5 pb-4 text-text no-underline">
                    <span class="pedestal flex aspect-square items-center justify-center overflow-hidden rounded-2xl">
                        <img src="{{ $b->imageSrc() }}" alt="{{ $b->name }} (صورة توضيحية)" loading="lazy" class="zoom h-[88%] w-[88%] object-contain">
                    </span>
                    <span class="flex items-baseline justify-between gap-1.5"><b class="font-display text-xl">{{ $b->category === \App\Enums\Category::Coin ? 'جنيه ذهب' : $b->weightLabel() }}</b><span class="text-xs text-muted">{{ $b->category === \App\Enums\Category::Coin ? '8 جم · عيار 21' : '999.9' }}</span></span>
                    <span class="text-[17px] font-bold text-gold-dark">≈ {{ number_format($b->price()) }} ج.م</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Why us --}}
    <section class="relative overflow-hidden bg-ink text-[#F4F2EE]">
        <div class="container-site relative flex flex-wrap items-center gap-x-16 gap-y-12 py-20">
            <div class="relative flex min-w-0 flex-[1_1_360px] justify-center">
                <div class="absolute inset-[6%] rounded-full" style="background: radial-gradient(closest-side, rgba(200,160,75,.28), rgba(200,160,75,0))" aria-hidden="true"></div>
                <img src="{{ asset('images/products/ring-solitaire.webp') }}" alt="خاتم سوليتير عيار 18 (صورة توضيحية)" loading="lazy" class="relative w-full max-w-[440px]">
            </div>
            <div class="flex min-w-0 flex-[1_1_520px] flex-col gap-8">
                <div class="flex flex-col gap-2.5">
                    <span class="eyebrow eyebrow-ink">ليه الحياة جولد</span>
                    <h2 class="m-0 font-display text-[2.1rem] font-bold leading-tight sm:text-[2.75rem]">محل ذهب حقيقي، <span class="text-gold-grad">بوابته على الإنترنت</span></h2>
                </div>
                <div class="grid gap-x-8 gap-y-7 [grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr))]">
                    @foreach ([
                        ['gem', 'المعروض موجود فعلاً', 'كل قطعة على الموقع متصوّرة من المحل، وجنبها الفروع اللي فيها دلوقتي.'],
                        ['lock', 'سعرك بيتثبّت وقت الطلب', 'سعر البيع والشراء قدامك قبل ما تأكد، ومفيش مفاجآت لما توصل الفرع.'],
                        ['box', 'استلام بمعاد من أقرب فرع', 'تختار الفرع والمعاد، وطلبك يبقى جاهز بفاتورة وضمان أول ما توصل.'],
                        ['swap', 'بدّل ذهبك القديم', 'هات ذهبك على أي فرع، يتوزن قدامك ويتحسب بسعر اللحظة، وخد بيه قطعة جديدة.'],
                    ] as [$icon, $title, $body])
                        <div class="flex flex-col gap-2.5 border-t border-ink-line pt-[18px]">
                            <x-icon :name="$icon" :size="26" :stroke="1.6" class="text-gold-bright" />
                            <b class="text-[19px]">{{ $title }}</b>
                            <span class="leading-[1.8] text-[#B5AC9B]">{{ $body }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Jewelry --}}
    <section id="jewelry" class="container-site flex flex-col gap-7 py-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex max-w-[640px] flex-col gap-2.5">
                <span class="eyebrow">المجوهرات</span>
                <h2 class="m-0 font-display text-[2.1rem] font-bold leading-tight sm:text-[2.75rem]">متوفر الآن في فروعنا</h2>
                <p class="m-0 text-[17px] leading-relaxed text-muted">المخزون بيتحدث من الفروع مباشرة. شوف القطعة في أنهي فرع، واحجزها قبل ما تنزل.</p>
            </div>
            <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 py-2.5 font-bold no-underline">كل المجوهرات <x-icon name="chevron" :size="18" /></a>
        </div>
        <nav aria-label="أنواع المجوهرات" class="flex flex-wrap gap-2.5">
            <a href="{{ route('home') }}#jewelry" class="chip" @if (! $filter) aria-current="true" @endif>الكل</a>
            @foreach (array_slice(\App\Enums\Category::filters(), 0, 5, true) as $slug => $label)
                <a href="{{ route('home', ['c' => $slug]) }}#jewelry" class="chip" @if ($filter === $slug) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="grid grid-cols-2 gap-x-3 gap-y-7 sm:gap-x-6 sm:[grid-template-columns:repeat(auto-fill,minmax(270px,1fr))]">
            @foreach ($jewelry as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>

    {{-- How it works + budget calculator --}}
    <section id="calc" class="border-y border-line bg-white">
        <div class="container-site flex flex-wrap items-stretch gap-x-16 gap-y-12 py-20">
            <div class="flex min-w-0 flex-[1_1_420px] flex-col gap-7">
                <div class="flex flex-col gap-2.5">
                    <span class="eyebrow">إزاي تطلب</span>
                    <h2 class="m-0 font-display text-[2.1rem] font-bold leading-tight sm:text-[2.75rem]">3 خطوات لحد ما الذهب في إيدك</h2>
                </div>
                <ol class="m-0 flex list-none flex-col p-0">
                    @foreach ([
                        ['اختار وزنك أو قطعتك', 'سبيكة أو جنيه أو قطعة من المعروض، والسعر قدامك بالمصنعية.'],
                        ['ثبّت السعر واختار الفرع', 'برقم موبايلك بس. ادفع عربون أونلاين أو ادفع كله في الفرع.'],
                        ['استلم بكود الطلب', 'وريهم الكود في الفرع، واستلم بفاتورة وضمان.'],
                    ] as $i => [$title, $body])
                        <li class="flex gap-5 border-t border-line py-5 {{ $loop->last ? 'border-b' : '' }}">
                            <span class="w-9 flex-none font-display text-[34px] leading-none text-gold">{{ $i + 1 }}</span>
                            <span class="flex flex-col gap-1.5"><b class="text-[19px]">{{ $title }}</b><span class="leading-[1.8] text-muted">{{ $body }}</span></span>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="relative flex min-w-0 flex-[1_1_400px] flex-col gap-5 overflow-hidden rounded-[28px] p-7 text-[#F4F2EE] shadow-[0_40px_80px_-40px_rgba(18,16,12,.7)] sm:p-9" style="background: linear-gradient(160deg, #221D15 0%, #12100C 70%)"
                 x-data="budget(@js($bars), 50000)">
                <div class="flex items-center justify-between gap-3"><h3 class="m-0 font-display text-[28px]">ميزانيتك تجيب كام؟</h3><span class="text-xs text-[#7D7466]">عيار 24 · 999.9</span></div>
                <label for="calc-amount" class="text-[15px] text-[#CFC6B4]">المبلغ (ج.م)</label>
                <input id="calc-amount" type="number" inputmode="numeric" min="0" step="1000" x-model.number="amount" dir="ltr" class="field field-ink min-h-[68px] text-right text-[34px] font-bold">
                <div class="flex flex-wrap gap-2">
                    @foreach ([10000, 25000, 50000, 100000] as $preset)
                        <button type="button" class="btn btn-sm btn-ghost-ink min-h-10 font-medium" @click="amount = {{ $preset }}">{{ number_format($preset) }}</button>
                    @endforeach
                </div>
                <div class="flex flex-col gap-1.5 rounded-[18px] border border-[#2E271D] bg-ink-2 p-[18px]" aria-live="polite">
                    <span class="text-sm text-muted-dark">تقدر تطلب</span>
                    <b class="text-gold-grad font-display text-[28px] leading-snug" x-text="label"></b>
                    <span class="text-sm text-[#CFC6B4]">إجمالي <span x-text="plan.grams.toLocaleString('en-US')"></span> جرام · ≈ <span x-text="$fmt(plan.spent)"></span> ج.م · يفضل معاك <span x-text="$fmt(plan.rest)"></span> ج.م</span>
                </div>
                <span class="text-[13px] text-[#7D7466]">على أساس سعر اللحظة والمصنعية، والسعر النهائي بيتثبّت وقت الطلب.</span>
                <a href="{{ route('bullion') }}" class="btn btn-lg btn-gold">اطلب السبائك</a>
            </div>
        </div>
    </section>

    {{-- Sell / trade-in --}}
    <section id="sell" class="container-site pb-10 pt-20">
        <div class="flex flex-wrap items-center gap-x-10 gap-y-6 overflow-hidden rounded-[32px] p-8 sm:p-12" style="background: linear-gradient(120deg, #F1E6CC 0%, #E9D7AE 100%)">
            <div class="flex min-w-0 flex-[1_1_420px] flex-col gap-3.5">
                <h2 class="m-0 font-display text-[2rem] font-bold leading-snug text-ink sm:text-[2.4rem]">عندك ذهب قديم؟ بيعه أو بدّله بسعر اللحظة</h2>
                <p class="m-0 text-[17px] leading-[1.8] text-[#3F3524]">اعرف قيمته التقريبية من هنا، واحجز معاد في الفرع. بيتوزن ويتفحص قدامك، وتاخد فلوسك أو قطعة جديدة.</p>
                <div class="flex flex-wrap gap-3 pt-1.5">
                    <a href="{{ route('sell') }}" class="btn btn-lg btn-ink">احسب قيمة ذهبك</a>
                    <a href="{{ route('sell') }}#book" class="btn btn-lg border border-gold-dark text-ink">احجز معاد في الفرع</a>
                </div>
            </div>
            <div class="flex min-w-0 flex-[0_1_300px] justify-center">
                <img src="{{ asset('images/products/bracelet-braided.webp') }}" alt="إسورة مضفّرة عيار 21 (صورة توضيحية)" loading="lazy" class="w-full max-w-[300px]">
            </div>
        </div>
    </section>

    {{-- Branches --}}
    <section id="branches" class="container-site flex flex-col gap-7 pb-20 pt-10">
        <div class="flex max-w-[640px] flex-col gap-2.5">
            <span class="eyebrow">الفروع</span>
            <h2 class="m-0 font-display text-[2.1rem] font-bold leading-tight sm:text-[2.75rem]">قريبين منك</h2>
            <p class="m-0 text-[17px] leading-relaxed text-muted">نفس السعر في كل فرع. احجز معاد استلام أو معاينة قطعة قبل ما تنزل.</p>
        </div>
        @include('site.partials.branches', ['branches' => $branches])
    </section>

    {{-- App --}}
    <section id="app" class="relative overflow-hidden text-[#F4F2EE]" style="background: radial-gradient(900px 500px at 80% 30%, #3A2E1A 0%, #12100C 70%)">
        <div class="star-pattern pointer-events-none absolute inset-0 opacity-[.06]" aria-hidden="true"></div>
        <div class="container-site relative flex flex-wrap items-end gap-x-16 gap-y-10 pt-20">
            <div class="flex min-w-0 flex-[1_1_460px] flex-col gap-5 pb-20">
                <h2 class="m-0 font-display text-[2.4rem] font-bold leading-tight sm:text-5xl">الحياة جولد <span class="text-gold-grad">في جيبك</span></h2>
                <p class="m-0 max-w-[520px] text-lg leading-[1.85] text-[#CFC6B4]">تنبيه لما السعر يوصل للرقم اللي مستنيه، وطلباتك وحجوزاتك وكود الاستلام، كله في تطبيق واحد.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="#ios" class="btn btn-ghost-ink min-h-[58px] gap-3 rounded-2xl"><x-icon name="phone" :size="22" class="text-gold-bright" /><span class="flex flex-col text-start leading-tight"><span class="text-xs font-normal text-muted-dark">حمّل من</span><b dir="ltr" class="text-[17px]">App Store</b></span></a>
                    <a href="#android" class="btn btn-ghost-ink min-h-[58px] gap-3 rounded-2xl"><x-icon name="arrow" :size="22" class="rotate-180 text-gold-bright" /><span class="flex flex-col text-start leading-tight"><span class="text-xs font-normal text-muted-dark">حمّل من</span><b dir="ltr" class="text-[17px]">Google Play</b></span></a>
                    <span class="flex size-[92px] items-center justify-center rounded-2xl bg-paper text-xs font-semibold text-muted">[QR]</span>
                </div>
            </div>
            <div class="flex h-[500px] w-[300px] max-w-full flex-none flex-col gap-3 overflow-hidden rounded-t-[46px] border-[10px] border-b-0 border-ink-3 bg-paper px-4 pt-5 text-text shadow-[0_-20px_80px_-20px_rgba(200,160,75,.35)]" aria-hidden="true">
                <div class="flex items-center justify-between"><x-logo :size="22" /><x-icon name="clock" /></div>
                <div class="flex flex-col gap-1.5 rounded-[20px] p-4 text-[#F4F2EE]" style="background: linear-gradient(150deg, #2A2318 0%, #12100C 75%)">
                    <span class="text-[11px] text-muted-dark">عيار 21 · الآن</span>
                    <b class="text-[26px]">{{ number_format($prices['quotes']['21']['sell']) }} <span class="text-xs font-medium text-gold">ج.م / جرام</span></b>
                    <x-sparkline :values="$sparks['21']" :height="40" stroke="#E6C77A" fill="rgba(0,0,0,0)" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="flex flex-col gap-1 rounded-2xl bg-white p-2 text-[11px]"><img src="{{ asset('images/products/bar10.webp') }}" alt="" class="aspect-square w-full rounded-[10px] bg-sand object-contain"><b>سبيكة 10 جم</b></div>
                    <div class="flex flex-col gap-1 rounded-2xl bg-white p-2 text-[11px]"><img src="{{ asset('images/products/necklace.webp') }}" alt="" class="aspect-square w-full rounded-[10px] bg-sand object-contain"><b>سلسلة بدلاية</b></div>
                </div>
                <div class="flex items-center gap-2.5 rounded-2xl bg-white p-3 text-xs"><span class="flex size-[34px] items-center justify-center rounded-[10px] bg-cream text-gold-dark"><x-icon name="grid" :size="18" /></span><span class="flex flex-col"><b>طلب AH-248133</b><span class="text-up">جاهز للاستلام</span></span></div>
            </div>
        </div>
    </section>
</x-layouts.site>

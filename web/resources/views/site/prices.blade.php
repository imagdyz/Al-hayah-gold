<x-layouts.site title="أسعار الذهب اليوم" description="سعر الذهب عيار 24 و21 و18 والجنيه الذهب لحظة بلحظة في الحياة جولد.">
    <x-page-head title="أسعار الذهب" lead="سعر البيع والشراء في كل فروعنا وفي التطبيق. بيتحدث كل دقيقة، وبيتثبّت لحظة ما تأكد طلبك.">
        <span class="inline-flex items-center gap-2 text-sm text-muted"><span class="live-dot"></span>آخر تحديث {{ optional(app(\App\Services\GoldPricing::class)->updatedAt())->locale('ar')->diffForHumans() ?? '—' }} · أسعار توضيحية</span>
    </x-page-head>

    <div class="container-site flex flex-col gap-8 py-10" x-data="pricesPage(@js($series), { karat: '{{ $karat }}', period: '{{ $period }}' })">
        <div class="grid gap-5 lg:grid-cols-[1fr_380px]">
            <section class="card flex min-w-0 flex-col gap-5 p-5 sm:p-7" aria-labelledby="chart-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="chart-title" class="m-0 font-display text-2xl">حركة السعر</h2>
                    <div class="flex flex-wrap gap-2">
                        <div class="flex gap-1 rounded-full bg-sand p-1" role="group" aria-label="العيار">
                            @foreach (['24' => 'عيار 24', '21' => 'عيار 21', '18' => 'عيار 18', 'coin' => 'الجنيه'] as $k => $label)
                                <button type="button" class="min-h-10 rounded-full px-3.5 text-sm font-semibold" :class="karat === '{{ $k }}' ? 'bg-white text-ink shadow-sm' : 'text-muted'" :aria-pressed="karat === '{{ $k }}'" @click="karat = '{{ $k }}'">{{ $label }}</button>
                            @endforeach
                        </div>
                        <div class="flex gap-1 rounded-full bg-sand p-1" role="group" aria-label="الفترة">
                            @foreach (['24h' => '24 ساعة', '7d' => 'أسبوع', '30d' => 'شهر'] as $p => $label)
                                <button type="button" class="min-h-10 rounded-full px-3.5 text-sm font-semibold" :class="period === '{{ $p }}' ? 'bg-ink text-white' : 'text-muted'" :aria-pressed="period === '{{ $p }}'" @click="period = '{{ $p }}'">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <template x-for="key in [karat + period]" :key="key">
                    <div x-data="lineChart(series, { width: 760, height: 300 })" class="flex flex-col gap-2">
                        <div class="flex min-h-[52px] flex-wrap items-baseline gap-x-3" aria-live="polite">
                            <b class="font-display text-4xl" x-text="$fmt((point ?? last).v)"></b>
                            <span class="text-sm text-muted" x-text="'ج.م · ' + when((point ?? last).t)"></span>
                        </div>
                        <div class="relative" dir="ltr">
                            <svg x-ref="svg" :viewBox="`0 0 ${w} ${h}`" class="block h-auto w-full touch-none select-none" role="img" aria-label="رسم بياني لسعر الذهب"
                                 @mousemove="move($event)" @touchmove.prevent="move($event)" @touchstart="move($event)" @mouseleave="hover = null">
                                <defs><linearGradient id="pg-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#C8A04B" stop-opacity=".22"/><stop offset="1" stop-color="#C8A04B" stop-opacity="0"/></linearGradient></defs>
                                @foreach ([0, 1, 2, 3] as $i)
                                    <line :x1="pad.l" :x2="w - pad.r" :y1="ticks[{{ $i }}].y" :y2="ticks[{{ $i }}].y" stroke="#ECE5D8" stroke-width="1"/>
                                    <text :x="pad.l - 8" :y="ticks[{{ $i }}].y + 4" text-anchor="end" font-size="11" fill="#5E584E" x-text="ticks[{{ $i }}].label"></text>
                                @endforeach
                                <path :d="area" fill="url(#pg-area)"/>
                                <path :d="line" fill="none" stroke="#A8853A" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                                <circle :cx="x(series.length - 1)" :cy="y(last.v)" r="4.5" fill="#A8853A" stroke="#fff" stroke-width="2"/>
                                <g x-show="point">
                                    <line :x1="x(hover ?? 0)" :x2="x(hover ?? 0)" :y1="pad.t" :y2="h - pad.b" stroke="#12100C" stroke-opacity=".25" stroke-dasharray="3 3"/>
                                    <circle :cx="x(hover ?? 0)" :cy="y((point ?? last).v)" r="5" fill="#12100C" stroke="#fff" stroke-width="2"/>
                                </g>
                            </svg>
                        </div>
                    </div>
                </template>
                <p class="m-0 text-xs text-muted">مرّر على الرسم أو المسه عشان تشوف السعر في أي وقت. سعر البيع للعميل.</p>
            </section>

            <aside class="flex flex-col gap-4" x-data>
                @foreach ($prices['quotes'] as $k => $q)
                    <div class="{{ $k === 'coin' ? 'card-ink' : 'card' }} flex items-center justify-between gap-4 p-5">
                        <div class="flex flex-col gap-1">
                            <b class="font-display text-xl">{{ $q['label'] }}</b>
                            <span class="text-[13px] {{ $k === 'coin' ? 'text-muted-dark' : 'text-muted' }}">{{ $q['unit'] }}</span>
                        </div>
                        <dl class="m-0 grid grid-cols-2 gap-x-5 text-end">
                            <dt class="text-xs {{ $k === 'coin' ? 'text-muted-dark' : 'text-muted' }}">نبيع</dt><dt class="text-xs {{ $k === 'coin' ? 'text-muted-dark' : 'text-muted' }}">نشتري</dt>
                            <dd class="m-0 rounded px-0.5 text-xl font-bold {{ $k === 'coin' ? 'text-gold-bright' : '' }}" :class="$store.prices.flash['{{ $k }}']" x-text="$store.prices.sell('{{ $k }}')">{{ number_format($q['sell']) }}</dd>
                            <dd class="m-0 text-xl font-semibold" x-text="$store.prices.buy('{{ $k }}')">{{ number_format($q['buy']) }}</dd>
                        </dl>
                    </div>
                @endforeach
                <a href="{{ route('bullion') }}" class="btn btn-lg btn-gold">اطلب سبيكة بالسعر ده</a>
                <a href="{{ route('sell') }}" class="btn btn-outline">احسب قيمة ذهبك القديم</a>
            </aside>
        </div>
        <p class="m-0 text-sm leading-relaxed text-muted">الجنيه الذهب = 8 جرام عيار 21. أسعار المشغولات بتتحسب بالوزن × سعر العيار + المصنعية.</p>
    </div>
</x-layouts.site>

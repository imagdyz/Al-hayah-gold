<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ isset($title) ? $title.' · ' : '' }}الحياة جولد</title>
    <meta name="description" content="{{ $description ?? 'سبائك وجنيهات ذهب بسعر اللحظة تستلمها من الفرع، ومجوهرات موجودة فعلاً في فروع الحياة جولد.' }}">
    <meta name="theme-color" content="#12100C">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>window.__PRICES = @json($prices);</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans" x-data="{ menu: false }">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-gold focus:px-4 focus:py-2 focus:text-ink">تخطّى للمحتوى</a>

    <header class="bg-ink text-on-ink">
        {{-- Live price strip --}}
        <div class="border-b border-ink-3">
            <div class="container-site flex flex-wrap items-center gap-x-7 gap-y-1 py-2 text-[13px]" x-data>
                <span class="inline-flex items-center gap-2 font-semibold tracking-wide text-gold"><span class="live-dot"></span>مباشر</span>
                @foreach (['24' => 'عيار 24', '21' => 'عيار 21', '18' => 'عيار 18', 'coin' => 'الجنيه'] as $k => $label)
                    <span class="text-muted-dark {{ $k === '18' ? 'max-sm:hidden' : '' }}">{{ $label }}
                        <b class="rounded px-1 font-semibold text-white" :class="$store.prices.flash['{{ $k }}']" x-text="$store.prices.sell('{{ $k }}')">{{ number_format($prices['quotes'][$k]['sell']) }}</b>
                    </span>
                @endforeach
                <span class="ms-auto text-[#7D7466] max-md:hidden">ج.م · السعر بيتحدث كل دقيقة</span>
            </div>
        </div>

        <div class="container-site flex items-center gap-x-9 gap-y-3 py-4">
            <a href="{{ route('home') }}" aria-label="الحياة جولد، الرئيسية" class="flex items-center no-underline">
                <x-logo :size="42" tone="dark" />
            </a>
            @php
                $nav = [
                    ['prices', 'أسعار الذهب'],
                    ['bullion', 'سبائك وجنيهات'],
                    ['shop.index', 'المجوهرات'],
                    ['sell', 'بيع ذهبك'],
                    ['branches', 'الفروع'],
                ];
            @endphp
            <nav aria-label="القائمة الرئيسية" class="flex gap-7 text-[15px] font-medium max-lg:hidden">
                @foreach ($nav as [$route, $label])
                    @php $active = request()->routeIs($route) || ($route === 'shop.index' && request()->routeIs('shop.*', 'reserve.*')); @endphp
                    <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                       class="py-2.5 no-underline transition-colors hover:text-gold-bright {{ $active ? 'font-bold text-gold-bright shadow-[inset_0_-2px_0_var(--color-gold)]' : 'text-on-ink' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="ms-auto flex items-center gap-2.5">
                @auth
                    <a href="{{ route('account') }}" class="btn btn-sm btn-ghost-ink max-sm:hidden"><x-icon name="user" :size="18" />طلباتي</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-ghost-ink max-sm:hidden">تسجيل الدخول</a>
                @endauth
                <a href="{{ route('home') }}#app" class="btn btn-sm btn-gold max-md:hidden">حمّل التطبيق</a>
                <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-[#4A3F2A] text-on-ink lg:hidden" @click="menu = !menu" :aria-expanded="menu" aria-controls="mobile-menu" aria-label="القائمة">
                    <x-icon name="menu" x-show="!menu" /><x-icon name="close" x-show="menu" x-cloak />
                </button>
            </div>
        </div>
        <nav id="mobile-menu" x-show="menu" x-cloak x-transition.opacity class="border-t border-ink-3 lg:hidden" aria-label="القائمة">
            <div class="container-site flex flex-col py-2">
                @foreach ($nav as [$route, $label])
                    <a href="{{ route($route) }}" class="border-b border-ink-3 py-3.5 text-on-ink no-underline">{{ $label }}</a>
                @endforeach
                @auth
                    <a href="{{ route('account') }}" class="py-3.5 font-semibold text-gold-bright no-underline">طلباتي</a>
                @else
                    <a href="{{ route('login') }}" class="py-3.5 font-semibold text-gold-bright no-underline">تسجيل الدخول</a>
                @endauth
            </div>
        </nav>
    </header>

    @if ($prices['halted'])
        <div class="bg-warn-bg text-warn">
            <div class="container-site flex items-center gap-3 py-3 text-sm font-medium"><x-icon name="pause" :size="18" />الطلبات أونلاين واقفة مؤقتاً بسبب تحرّك السعر. الأسعار للعرض بس لحد ما نرجّعها.</div>
        </div>
    @endif

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="bg-[#0A0907] text-sm text-muted-dark">
        <div class="container-site flex flex-wrap gap-x-16 gap-y-8 pb-8 pt-14">
            <div class="flex min-w-[260px] flex-1 flex-col gap-3.5">
                <x-logo :size="42" tone="dark" />
                <span class="max-w-[320px] leading-relaxed">ذهب ومجوهرات من [سنة التأسيس]. [رقم السجل التجاري] · [بيانات الترخيص]</span>
            </div>
            <div class="flex flex-col gap-2.5"><b class="text-[#F4F2EE]">تسوّق</b>
                <a href="{{ route('prices') }}" class="text-muted-dark no-underline hover:text-gold-bright">أسعار الذهب</a>
                <a href="{{ route('bullion') }}" class="text-muted-dark no-underline hover:text-gold-bright">سبائك وجنيهات</a>
                <a href="{{ route('shop.index') }}" class="text-muted-dark no-underline hover:text-gold-bright">المجوهرات</a>
                <a href="{{ route('sell') }}" class="text-muted-dark no-underline hover:text-gold-bright">بيع ذهبك</a>
            </div>
            <div class="flex flex-col gap-2.5"><b class="text-[#F4F2EE]">المساعدة</b>
                <a href="{{ route('page', 'faq') }}" class="text-muted-dark no-underline hover:text-gold-bright">الأسئلة الشائعة</a>
                <a href="{{ route('page', 'terms') }}" class="text-muted-dark no-underline hover:text-gold-bright">الشروط والأحكام</a>
                <a href="{{ route('page', 'privacy') }}" class="text-muted-dark no-underline hover:text-gold-bright">سياسة الخصوصية</a>
            </div>
            <div class="flex flex-col gap-2.5"><b class="text-[#F4F2EE]">تواصل معنا</b><span>[رقم خدمة العملاء]</span><span>[البريد الإلكتروني]</span></div>
        </div>
        <div class="container-site flex flex-wrap justify-between gap-2 border-t border-[#221D16] pb-7 pt-4">
            <span>© {{ date('Y') }} الحياة جولد. جميع الحقوق محفوظة.</span>
            <span>صور المنتجات توضيحية لحد التصوير من المحل.@if (config('gold.source') === 'daleelak') · مصدر الأسعار: <a href="https://getdaleelak.com" rel="noopener" target="_blank" class="text-muted-dark">دليلك</a>@endif</span>
        </div>
    </footer>
</body>
</html>

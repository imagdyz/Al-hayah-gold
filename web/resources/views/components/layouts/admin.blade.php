@props(['title' => 'لوحة التحكم'])
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · لوحة تحكم الحياة جولد</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans">
    <div class="flex flex-wrap">
        <aside class="flex w-full flex-col gap-6 bg-ink p-5 text-on-ink lg:min-h-screen lg:w-[248px] lg:flex-none print:hidden">
            <a href="{{ route('admin.dashboard') }}" class="no-underline"><x-logo :size="36" tone="dark" /></a>
            @php
                $links = [
                    ['admin.dashboard', 'نظرة عامة', 'chart', 'admin.dashboard'],
                    ['admin.pos', 'الكاشير', 'swap', 'admin.pos*'],
                    ['admin.invoices.index', 'الفواتير', 'receipt', 'admin.invoices.*'],
                    ['admin.products.index', 'المنتجات والمخزون', 'gem', 'admin.products.*'],
                    ['admin.pieces.index', 'القطع بالكود', 'barcode', 'admin.pieces.*'],
                    ['admin.reports', 'التقارير', 'report', 'admin.reports'],
                    ['admin.prices', 'الأسعار والهوامش', 'tag', 'admin.prices*'],
                    ['admin.orders.index', 'طلبات الموقع', 'calendar', 'admin.orders.*'],
                    ['admin.branches.index', 'الفروع', 'store', 'admin.branches.*'],
                    ['admin.shop', 'بيانات المحل', 'info', 'admin.shop*'],
                ];
            @endphp
            <nav aria-label="لوحة التحكم" class="flex flex-wrap gap-1 lg:flex-col">
                @foreach ($links as [$route, $label, $icon, $pattern])
                    @php $on = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}" @if ($on) aria-current="page" @endif
                       class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-[15px] no-underline {{ $on ? 'bg-ink-3 font-bold text-gold-bright' : 'text-on-ink hover:bg-ink-2' }}">
                        <x-icon :name="$icon" :size="19" />{{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="mt-auto flex flex-col gap-2 border-t border-ink-3 pt-4 text-sm text-muted-dark">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-muted-dark no-underline hover:text-gold-bright" target="_blank"><x-icon name="eye" :size="17" />افتح الموقع</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex items-center gap-2 hover:text-gold-bright"><x-icon name="logout" :size="17" />خروج ({{ auth()->user()->name }})</button></form>
            </div>
        </aside>
        <main class="min-w-0 flex-[999_1_560px] p-5 sm:p-8">
            <div class="mx-auto flex max-w-[1240px] flex-col gap-6">
                <x-flash />
                @if ($errors->any() && ! $errors->has('trading'))
                    <div role="alert" class="rounded-2xl bg-down-bg px-4 py-3 text-sm text-[#8C1D18]">راجع البيانات: {{ $errors->first() }}</div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>

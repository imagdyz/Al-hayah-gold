@props(['title', 'crumbs' => [], 'lead' => null])
<section class="border-b border-[#E6DECF]" style="background: linear-gradient(180deg, #EFE7D8 0%, #F6F3EE 100%)">
    <div class="container-site flex flex-wrap items-end justify-between gap-5 pb-9 pt-7">
        <div class="flex min-w-0 flex-col gap-3">
            <nav aria-label="مسار الصفحة" class="flex flex-wrap gap-2 text-sm text-muted">
                <a href="{{ route('home') }}" class="text-gold-dark no-underline hover:underline">الرئيسية</a>
                @foreach ($crumbs as $label => $url)
                    <span aria-hidden="true">/</span><a href="{{ $url }}" class="text-gold-dark no-underline hover:underline">{{ $label }}</a>
                @endforeach
                <span aria-hidden="true">/</span><span>{{ $title }}</span>
            </nav>
            <h1 class="m-0 font-display text-[2.4rem] font-bold leading-tight sm:text-[3.25rem]">{{ $title }}</h1>
            @if ($lead)<p class="m-0 max-w-[640px] text-[17px] leading-relaxed text-muted">{{ $lead }}</p>@endif
        </div>
        {{ $slot }}
    </div>
</section>

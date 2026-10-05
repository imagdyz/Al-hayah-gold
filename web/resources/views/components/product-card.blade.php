@props(['product', 'ratio' => '4 / 5'])
<a href="{{ route('shop.show', $product) }}" {{ $attributes->merge(['class' => 'lift group flex flex-col gap-3.5 rounded-3xl text-text no-underline']) }}>
    <span class="pedestal relative flex items-center justify-center overflow-hidden rounded-3xl" style="aspect-ratio: {{ $ratio }}">
        <img src="{{ $product->imageSrc() }}" alt="{{ $product->name }} {{ $product->karatLabel() }} (صورة توضيحية)" loading="lazy" class="zoom h-[80%] w-[80%] object-contain">
        <span class="pill absolute start-2 top-2 bg-white/90 text-up backdrop-blur max-sm:text-[11px] sm:start-3 sm:top-3"><span class="size-1.5 rounded-full bg-up"></span>{{ $product->stockLabel() }}</span>
        <span class="pill pill-ink absolute end-2 top-2 max-sm:hidden sm:end-3 sm:top-3">{{ $product->karatLabel() }}</span>
    </span>
    <span class="flex flex-col gap-1 px-1">
        <b class="font-display text-base font-semibold sm:text-[1.2rem]">{{ $product->name }}</b>
        <span class="text-sm text-muted">{{ $product->weightLabel() }}@if ($product->subtitle) · {{ $product->subtitle }}@endif</span>
        <span class="text-[15px] font-bold text-gold-dark sm:text-lg">≈ {{ number_format($product->price()) }} ج.م</span>
    </span>
</a>

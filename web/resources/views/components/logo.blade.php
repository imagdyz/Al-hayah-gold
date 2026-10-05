@props(['size' => 40, 'tone' => 'light', 'layout' => 'horizontal'])
@php
    $dark = $tone === 'dark';
    $mark = $dark ? 'url(#hg-logo-gold)' : '#8A6A1F';
    $name = $dark ? '#F4F2EE' : '#12100C';
    $latin = $dark ? '#C8A04B' : '#6B6458';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center']) }} style="gap: {{ round($size * .25) }}px">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="{{ $mark }}" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true" class="flex-none">
        @if ($dark)
            <defs><linearGradient id="hg-logo-gold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#F3DE9C"/><stop offset=".45" stop-color="#C8A04B"/><stop offset=".7" stop-color="#9C7A2C"/><stop offset="1" stop-color="#E6C77A"/></linearGradient></defs>
        @endif
        <rect x="11" y="11" width="26" height="26"/>
        <rect x="11" y="11" width="26" height="26" transform="rotate(45 24 24)"/>
        <circle cx="24" cy="24" r="5.5"/>
    </svg>
    @if ($layout !== 'mark')
        <span class="flex flex-col leading-[1.1]" style="gap: {{ round($size * .04, 1) }}px">
            <span class="font-display font-bold whitespace-nowrap" style="font-size: {{ round($size * .6, 1) }}px; color: {{ $name }}">الحياة جولد</span>
            <span dir="ltr" class="font-semibold whitespace-nowrap" style="font-size: {{ max(round($size * .25, 1), 9) }}px; letter-spacing: {{ round(max($size * .25, 9) * .3, 1) }}px; color: {{ $latin }}">AL HAYAH GOLD</span>
        </span>
    @endif
</span>

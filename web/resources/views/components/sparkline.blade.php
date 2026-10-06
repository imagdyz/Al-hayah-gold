@props(['values', 'width' => 240, 'height' => 54, 'stroke' => '#A8853A', 'fill' => 'rgba(200,160,75,.12)'])
@php
    $vals = array_values($values);
@endphp
@if (count($vals))
@php
    $min = min($vals); $max = max($vals); $span = max($max - $min, 1);
    $n = max(count($vals) - 1, 1);
    $pts = [];
    foreach ($vals as $i => $v) {
        $pts[] = [round($i * $width / $n, 1), round($height - 6 - ($v - $min) / $span * ($height - 14), 1)];
    }
    $line = 'M'.implode(' L', array_map(fn ($p) => $p[0].' '.$p[1], $pts));
    $last = end($pts);
@endphp
<svg width="100%" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" aria-hidden="true" {{ $attributes }}>
    <path d="{{ $line }} L{{ $width }} {{ $height }} L0 {{ $height }} Z" fill="{{ $fill }}"/>
    <path d="{{ $line }}" fill="none" stroke="{{ $stroke }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
</svg>
@endif

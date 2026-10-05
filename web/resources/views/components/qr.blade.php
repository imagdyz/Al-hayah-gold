@props(['data', 'size' => 160])
@php
    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
        new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 0),
        new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
    );
    $svg = (new \BaconQrCode\Writer($renderer))->writeString($data);
    $svg = preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);
@endphp
<span role="img" aria-label="كود QR للطلب {{ $data }}" {{ $attributes->merge(['class' => 'inline-block rounded-xl bg-white p-2']) }}>{!! $svg !!}</span>

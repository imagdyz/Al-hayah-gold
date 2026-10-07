<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تيكت باركود</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: 50mm 25mm; margin: 0; }
        html, body { margin: 0; background: #fff; }
        body { font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif; color: #000; }
        .label { width: 50mm; height: 25mm; box-sizing: border-box; padding: 1.5mm 2mm; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; break-after: page; }
        .label:last-child { break-after: auto; }
        .top { display: flex; justify-content: space-between; font-size: 7pt; line-height: 1.2; font-weight: 700; }
        .bars { height: 10mm; }
        .bars svg { width: 100%; height: 100%; display: block; }
        .code { text-align: center; font-family: monospace; font-size: 7.5pt; letter-spacing: .5px; direction: ltr; line-height: 1; }
        .bottom { display: flex; justify-content: space-between; font-size: 7pt; line-height: 1.2; }
        @media screen { body { background: #e9e5dd; padding: 16px; display: flex; flex-wrap: wrap; gap: 8px; } .label { background: #fff; outline: 1px dashed #aaa; } }
    </style>
</head>
<body>
@foreach ($pieces as $p)
    <div class="label">
        <div class="top"><span>{{ \Illuminate\Support\Str::limit($p->name, 24) }}</span><span>ع{{ $p->karat }}</span></div>
        <div class="bars">{!! \App\Support\Code128::svg($p->barcode, 40) !!}</div>
        <div class="code">{{ $p->barcode }}</div>
        <div class="bottom"><span>وزن <b dir="ltr">{{ rtrim(rtrim(number_format((float) $p->weight_g, 3), '0'), '.') }}</b> جم</span><span>مصنعية <b>{{ number_format($p->making_fee) }}</b></span></div>
    </div>
@endforeach
@if ($auto)<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>@endif
</body>
</html>

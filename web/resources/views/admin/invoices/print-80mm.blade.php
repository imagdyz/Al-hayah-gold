@php
    $sales = $invoice->lines->where('kind', \App\Enums\LineKind::Sale)->values();
    $purchases = $invoice->lines->where('kind', \App\Enums\LineKind::Purchase)->values();
    $w = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إيصال {{ $invoice->label() }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: 80mm 200mm; margin: 0; }
        html, body { background: #fff; }
        body { font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif; color: #000; font-size: 10pt; line-height: 1.45; margin: 0; }
        .slip { width: 72mm; margin: 0 auto; padding: 3mm 0; }
        @media screen { body { background: #e9e5dd; padding: 16px; } .slip { background: #fff; padding: 4mm; } }
        .c { text-align: center; }
        .brand { font-family: 'El Messiri', serif; font-size: 16pt; font-weight: 700; line-height: 1.2; }
        hr { border: 0; border-top: 1px dashed #000; margin: 2mm 0; }
        .row { display: flex; justify-content: space-between; gap: 2mm; }
        .item { margin-bottom: 1.5mm; }
        .item .sub { font-size: 8.5pt; }
        h3 { margin: 1mm 0; font-size: 10.5pt; text-align: center; }
        .big { font-size: 13pt; font-weight: 700; }
        .num { direction: ltr; unicode-bidi: isolate; }
        .void { text-align: center; font-weight: 700; font-size: 14pt; border: 2px solid #000; margin: 2mm 0; }
    </style>
</head>
<body>
<div class="slip">
    <div class="c">
        <div class="brand">{{ $shop['name'] }}</div>
        @if ($shop['tagline'])<div>{{ $shop['tagline'] }}</div>@endif
        @if ($shop['address'])<div style="font-size:8.5pt">{{ $shop['address'] }}</div>@endif
        @if ($shop['phone'])<div style="font-size:8.5pt" class="num">{{ $shop['phone'] }}</div>@endif
    </div>
    <hr>
    <div class="row"><span>فاتورة رقم</span><b class="num">{{ $invoice->label() }}</b></div>
    <div class="row"><span>التاريخ</span><span class="num">{{ $invoice->issued_at->format('Y/m/d g:i A') }}</span></div>
    @if ($invoice->customer_name)<div class="row"><span>العميل</span><span>{{ $invoice->customer_name }}</span></div>@endif
    @if ($invoice->isVoided())<div class="void">ملغاة</div>@endif

    @if ($sales->isNotEmpty())
        <hr><h3>بيع</h3>
        @foreach ($sales as $l)
            <div class="item">
                <div class="row"><b>{{ $l->description }}</b><b>{{ number_format($l->total) }}</b></div>
                <div class="sub">عيار {{ $l->karat }} · <span class="num">{{ $w($l->net_weight) }}</span> جم × {{ number_format($l->gram_price) }} + مصنعية {{ number_format($l->making_fee) }}</div>
            </div>
        @endforeach
        <div class="row"><span>إجمالي البيع</span><b>{{ number_format($invoice->sales_total) }}</b></div>
    @endif

    @if ($purchases->isNotEmpty())
        <hr><h3>شراء</h3>
        @foreach ($purchases as $l)
            <div class="item">
                <div class="row"><b>{{ $l->description }}</b><b>{{ number_format($l->total) }}</b></div>
                <div class="sub">عيار {{ $l->karat }} · إجمالي <span class="num">{{ $w($l->gross_weight) }}</span> · تحييف <span class="num">{{ $w($l->net_weight) }}</span> جم × {{ number_format($l->gram_price) }}</div>
            </div>
        @endforeach
        <div class="row"><span>إجمالي الشراء</span><b>{{ number_format($invoice->purchases_total) }}</b></div>
    @endif

    <hr>
    <div class="row big"><span>{{ $invoice->netLabel() }}</span><span>{{ number_format(abs($invoice->net)) }} ج.م</span></div>
    <div class="row"><span>طريقة التسوية</span><span>{{ $invoice->settlement->label() }}</span></div>
    <hr>
    <div class="c" style="font-size:8.5pt">شكراً لتعاملكم معنا</div>
</div>
<script>
    // Roll paper: size the page to the slip so the printer doesn't feed blank paper.
    window.addEventListener('load', () => {
        const mm = Math.ceil(document.querySelector('.slip').getBoundingClientRect().height * 25.4 / 96) + 6;
        const style = document.createElement('style');
        style.textContent = `@page { size: 80mm ${mm}mm; margin: 0; }`;
        document.head.appendChild(style);
        @if ($auto) setTimeout(() => window.print(), 300); @endif
    });
</script>
</body>
</html>

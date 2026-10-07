@php
    $sales = $invoice->lines->where('kind', \App\Enums\LineKind::Sale)->values();
    $purchases = $invoice->lines->where('kind', \App\Enums\LineKind::Purchase)->values();
    $w = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة {{ $invoice->label() }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A5 landscape; margin: 7mm; }
        html, body { background: #fff; }
        body { font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif; color: #111; font-size: 10.5pt; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sheet { width: 196mm; min-height: 134mm; margin: 0 auto; position: relative; }
        @media screen { body { background: #e9e5dd; padding: 16px; } .sheet { background: #fff; padding: 7mm; box-shadow: 0 4px 24px rgba(0,0,0,.12); } }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1.5pt solid #111; padding-bottom: 2.5mm; }
        .brand b { display: block; font-family: 'El Messiri', serif; font-size: 21pt; line-height: 1.15; }
        .brand span { display: block; font-size: 10pt; }
        .brand small { display: block; font-size: 9pt; color: #333; }
        .no { text-align: left; }
        .no b { font-family: monospace; font-size: 19pt; color: #b3261e; letter-spacing: 1px; }
        .meta { display: flex; justify-content: space-between; margin: 2mm 0; font-size: 10pt; }
        h2 { text-align: center; font-family: 'El Messiri', serif; font-size: 13pt; margin: 2mm 0 1mm; letter-spacing: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.8pt solid #111; padding: 1.2mm 1.5mm; text-align: center; font-size: 9.5pt; }
        th { background: #e4e0d8; font-weight: 700; }
        td.desc { text-align: right; }
        tr.total td { font-weight: 700; background: #f4f1ea; }
        .foot { display: flex; justify-content: space-between; align-items: flex-end; gap: 6mm; margin-top: 3mm; font-size: 10pt; }
        .net { border: 1.2pt solid #111; border-radius: 2mm; padding: 1.5mm 3mm; font-weight: 700; }
        .net b { font-size: 13pt; }
        .opt { margin-inline-start: 4mm; white-space: nowrap; }
        .box { display: inline-block; width: 3.4mm; height: 3.4mm; border: 0.9pt solid #111; vertical-align: middle; margin-inline-start: 1.2mm; text-align: center; line-height: 3mm; font-size: 9pt; font-weight: 700; }
        .sign { display: flex; justify-content: space-between; margin-top: 5mm; font-size: 10pt; }
        .sign span { display: inline-block; width: 45mm; border-bottom: 0.8pt dotted #111; }
        .void { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 60pt; font-weight: 700; color: rgba(179, 38, 30, .18); transform: rotate(-18deg); pointer-events: none; }
        .num { direction: ltr; unicode-bidi: isolate; }
    </style>
</head>
<body>
<div class="sheet">
    @if ($invoice->isVoided())<div class="void">ملغاة</div>@endif
    <div class="head">
        <div class="brand">
            <b>{{ $shop['name'] }}</b>
            @if ($shop['tagline'])<span>{{ $shop['tagline'] }}</span>@endif
            @if ($shop['address'])<small>{{ $shop['address'] }}</small>@endif
            @if ($shop['phone'])<small>ت: <span class="num">{{ $shop['phone'] }}</span></small>@endif
        </div>
        <div class="no">No. <b>{{ $invoice->label() }}</b></div>
    </div>
    <div class="meta">
        <span>التاريخ: <b class="num">{{ $invoice->issued_at->format('Y/m/d') }}</b></span>
        <span>العميل: <b>{{ $invoice->customer_name ?: '....................' }}</b>@if ($invoice->customer_phone) · <span class="num">{{ $invoice->customer_phone }}</span>@endif</span>
        <span>الوقت: <b class="num">{{ $invoice->issued_at->format('g:i A') }}</b></span>
    </div>

    @if ($sales->isNotEmpty())
        <h2>فاتورة بيع</h2>
        <table>
            <thead><tr><th style="width:6mm">م</th><th>البيان</th><th>العيار</th><th>الوزن القائم (جم)</th><th>سعر جرام البورصة</th><th>المصنعية</th><th>الإجمالي النقدي</th></tr></thead>
            <tbody>
                @foreach ($sales as $i => $l)
                    <tr><td>{{ $i + 1 }}</td><td class="desc">{{ $l->description }}</td><td>{{ $l->karat }}</td><td class="num">{{ $w($l->net_weight) }}</td><td>{{ number_format($l->gram_price) }}</td><td>{{ number_format($l->making_fee) }}</td><td>{{ number_format($l->total) }}</td></tr>
                @endforeach
                <tr class="total"><td colspan="6" class="desc">إجمالي النقدية المستلمة</td><td>{{ number_format($invoice->sales_total) }}</td></tr>
            </tbody>
        </table>
    @endif

    @if ($purchases->isNotEmpty())
        <h2>فاتورة الشراء</h2>
        <table>
            <thead><tr><th>بيان الذهب الوارد</th><th>العيار</th><th>الوزن الإجمالي</th><th>وزن التحييف</th><th>سعر الشراء</th><th>الإجمالي</th></tr></thead>
            <tbody>
                @foreach ($purchases as $l)
                    <tr><td class="desc">{{ $l->description }}</td><td>{{ $l->karat }}</td><td class="num">{{ $w($l->gross_weight) }}</td><td class="num">{{ $w($l->net_weight) }}</td><td>{{ number_format($l->gram_price) }}</td><td>{{ number_format($l->total) }}</td></tr>
                @endforeach
                <tr class="total"><td colspan="5" class="desc">إجمالي الشراء</td><td>{{ number_format($invoice->purchases_total) }}</td></tr>
            </tbody>
        </table>
    @endif

    <div class="foot">
        <div>طريقة التسوية:
            @foreach (\App\Enums\Settlement::cases() as $s)
                <span class="opt">{{ $s->label() }}<span class="box">{{ $invoice->settlement === $s ? '✓' : '' }}</span></span>
            @endforeach
        </div>
        @if ($sales->isNotEmpty() && $purchases->isNotEmpty())
            <div class="net">{{ $invoice->netLabel() }}: <b>{{ number_format(abs($invoice->net)) }}</b> ج.م</div>
        @endif
    </div>
    <div class="sign"><div>توقيع البائع: <span></span></div><div>توقيع العميل: <span></span></div></div>
</div>
@if ($auto)<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>@endif
</body>
</html>

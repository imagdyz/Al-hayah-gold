@php use App\Enums\OrderStatus; @endphp
<x-layouts.admin :title="'طلب '.$order->code">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-1"><a href="{{ route('admin.orders.index') }}" class="text-sm">← كل الطلبات</a><h1 class="m-0 font-display text-[2rem] font-bold">{{ $order->type->label() }} <span dir="ltr">{{ $order->code }}</span></h1></div>
        <x-status-pill :status="$order->status" class="text-base" />
    </div>
    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <section class="card flex flex-col gap-4 p-6">
            <div class="flex items-center gap-4">
                <span class="pedestal flex size-24 flex-none items-center justify-center rounded-2xl"><img src="{{ $order->imageSrc() }}" alt="" class="size-20 object-contain"></span>
                <div class="flex flex-col gap-1"><b class="font-display text-2xl">{{ $order->title() }}</b><span class="text-muted">{{ $order->branch->fullName() }}</span></div>
            </div>
            <dl class="m-0 grid gap-x-8 sm:grid-cols-2">
                @foreach ([
                    'العميل' => ($order->customer_name ?: '—'),
                    'الموبايل' => $order->phone,
                    'الإجمالي' => number_format((float) $order->total).' ج.م',
                    'سعر الوحدة' => number_format((float) $order->unit_price).' ج.م',
                    'الوزن' => $order->weight_g ? rtrim(rtrim((string) $order->weight_g, '0'), '.').' جم' : '—',
                    'العيار' => $order->karat ?: '—',
                    'الدفع' => $order->payLabel(),
                    'العربون' => (float) $order->deposit ? number_format((float) $order->deposit).' ج.م' : '—',
                    'المعاد' => $order->slot_at?->locale('ar')->translatedFormat('l j F، g:i a') ?? '—',
                    'السعر ثابت لحد' => $order->locked_until?->locale('ar')->translatedFormat('j F، g:i a') ?? '—',
                    'اتعمل' => $order->created_at->locale('ar')->translatedFormat('j F، g:i a'),
                ] as $dt => $dd)
                    <div class="flex justify-between gap-3 border-b border-[#F0EADF] py-2.5"><dt class="text-muted">{{ $dt }}</dt><dd class="m-0 font-semibold" @if ($dt === 'الموبايل') dir="ltr" @endif>{{ $dd }}</dd></div>
                @endforeach
            </dl>
            @if ($order->notes)<p class="m-0 rounded-xl bg-paper p-3 text-sm"><b>ملاحظات العميل:</b> {{ $order->notes }}</p>@endif
        </section>
        <aside class="card flex flex-col gap-3 p-6">
            <h2 class="m-0 text-lg font-bold">غيّر الحالة</h2>
            @foreach ([
                [OrderStatus::Confirmed, 'أكّد الطلب', 'btn-outline'],
                [OrderStatus::Ready, 'جاهز. ابعت للعميل', 'btn-ink'],
                [OrderStatus::Completed, 'اتسلّم / اتقفل', 'btn-gold'],
                [OrderStatus::Cancelled, 'إلغاء ورجّع المخزون', 'text-down border border-down/30 bg-white'],
            ] as [$s, $label, $cls])
                @if ($order->status !== $s)
                    <form method="POST" action="{{ route('admin.orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $s->value }}"><button class="btn {{ $cls }} w-full">{{ $label }}</button></form>
                @endif
            @endforeach
            <x-qr :data="$order->code" :size="140" class="mx-auto mt-2 border border-line" />
        </aside>
    </div>
</x-layouts.admin>

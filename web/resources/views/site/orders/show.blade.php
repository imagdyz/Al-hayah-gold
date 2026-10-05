@php use App\Enums\OrderType; @endphp
<x-layouts.site :title="'طلب '.$order->code">
    <section class="relative overflow-hidden text-[#F4F2EE]" style="background: radial-gradient(circle at 50% 20%, #3A2E1A 0%, #17140F 60%, #12100C 100%)">
        <div class="star-pattern absolute inset-0 opacity-[.06]" aria-hidden="true"></div>
        <div class="container-site relative flex flex-col items-center gap-3 py-12 text-center">
            <span class="flex size-16 items-center justify-center rounded-full bg-gradient-to-b from-[#EACD80] to-gold text-ink shadow-[0_0_0_8px_rgba(200,160,75,.15)]"><x-icon name="check" :size="30" :stroke="2.4" /></span>
            <h1 class="m-0 font-display text-[2rem] font-bold sm:text-[2.4rem]">
                {{ match ($order->type) { OrderType::Bullion => 'طلبك اتأكد وسعرك اتثبّت', OrderType::Reservation => 'القطعة محجوزة لك', OrderType::Sell => 'معادك في الفرع اتأكد' } }}
            </h1>
            <p class="m-0 text-[#CFC6B4]">{{ $order->title() }} · {{ $order->branch->fullName() }}</p>
            <x-status-pill :status="$order->status" class="mt-1" />
        </div>
    </section>

    <div class="container-site grid gap-6 py-10 pb-20 lg:grid-cols-[380px_1fr]">
        <div class="card flex flex-col items-center gap-4 p-7 text-center shadow-raised">
            <x-qr :data="$order->code" :size="200" />
            <span class="text-sm text-muted">كود الطلب</span>
            <b class="font-display text-3xl tracking-wider" dir="ltr">{{ $order->code }}</b>
            <span class="text-sm leading-relaxed text-muted">وري الكود ده في الفرع، وهات بطاقتك الشخصية.</span>
        </div>
        <div class="flex flex-col gap-5">
            <x-flash />
            <dl class="card m-0 flex flex-col px-6 py-2 text-[15px]">
                @php
                    $rows = [];
                    if ($order->type === OrderType::Sell) {
                        $rows['العيار والوزن'] = 'عيار '.$order->karat.' · حوالي '.rtrim(rtrim(number_format((float) $order->weight_g, 3, '.', ''), '0'), '.').' جم';
                        $rows['القيمة التقريبية'] = number_format((float) $order->total).' ج.م';
                        $rows['سعر الشراء وقت الحجز'] = number_format((float) $order->unit_price).' ج.م / جم';
                    } else {
                        $rows['السعر'] = number_format((float) $order->total).' ج.م';
                        if ($order->quantity > 1) $rows['سعر الوحدة'] = number_format((float) $order->unit_price).' ج.م × '.$order->quantity;
                        if ((float) $order->deposit > 0) $rows['العربون'] = number_format((float) $order->deposit).' ج.م · [يتدفع أونلاين مع تفعيل بوابة الدفع]';
                        if ($order->locked_until) $rows[$order->type === OrderType::Reservation ? 'محجوزة لحد' : 'السعر ثابت لحد'] = $order->locked_until->locale('ar')->translatedFormat('l j F، g:i a');
                    }
                    $rows['الدفع'] = $order->payLabel();
                    if ($order->slot_at) $rows['المعاد'] = $order->slot_at->locale('ar')->translatedFormat('l j F، g:i a');
                    $rows['اتعمل'] = $order->created_at->locale('ar')->diffForHumans();
                @endphp
                @foreach ($rows as $dt => $dd)
                    <div class="flex justify-between gap-4 py-3 [&:not(:last-child)]:border-b [&:not(:last-child)]:border-[#F0EADF]"><dt class="text-muted">{{ $dt }}</dt><dd class="m-0 text-end font-bold">{{ $dd }}</dd></div>
                @endforeach
            </dl>
            <div class="card flex items-center gap-3.5 p-4">
                <span class="flex size-11 flex-none items-center justify-center rounded-xl bg-cream text-gold-dark"><x-icon name="pin" /></span>
                <span class="flex flex-1 flex-col"><b>{{ $order->branch->fullName() }}</b><span class="text-sm text-muted">{{ $order->branch->address }} · {{ $order->branch->hoursLabel() }}</span></span>
                <a href="{{ $order->branch->map_url ?: route('branches') }}" class="btn btn-sm btn-ink">الاتجاهات</a>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('account') }}" class="btn btn-ink">كل طلباتي</a>
                <a href="{{ route('home') }}" class="btn btn-outline">الرئيسية</a>
            </div>
        </div>
    </div>
</x-layouts.site>

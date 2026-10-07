<x-layouts.admin title="بيانات المحل">
    <div><h1 class="m-0 font-display text-[2rem] font-bold">بيانات المحل</h1><p class="m-0 text-muted">دي اللي بتتطبع في راس الفاتورة.</p></div>
    <form method="POST" action="{{ route('admin.shop.update') }}" class="card grid max-w-[760px] gap-5 p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <div><label for="shop_name" class="label">اسم المحل</label><input id="shop_name" name="shop_name" value="{{ old('shop_name', $values['shop_name']) }}" required maxlength="60" class="field"></div>
        <div><label for="shop_tagline" class="label">تحت الاسم</label><input id="shop_tagline" name="shop_tagline" value="{{ old('shop_tagline', $values['shop_tagline']) }}" maxlength="80" class="field"></div>
        <div><label for="shop_address" class="label">العنوان</label><input id="shop_address" name="shop_address" value="{{ old('shop_address', $values['shop_address']) }}" maxlength="120" class="field"></div>
        <div><label for="shop_phone" class="label">التليفون</label><input id="shop_phone" name="shop_phone" value="{{ old('shop_phone', $values['shop_phone']) }}" maxlength="40" class="field" dir="ltr"></div>
        <div><label for="invoice_start" class="label">رقم أول فاتورة</label><input id="invoice_start" name="invoice_start" type="number" min="1" value="{{ old('invoice_start', $values['invoice_start']) }}" required class="field" dir="ltr">
            <p class="m-0 mt-1 text-xs text-muted">عشان الترقيم يكمّل بعد آخر رقم في دفتر الفواتير الورق. @if ($lastNumber)آخر فاتورة على السيستم رقم {{ $lastNumber }}، والجاية هتاخد الأكبر من الرقمين.@endif</p></div>
        <div class="flex items-end"><button class="btn btn-lg btn-gold w-full">احفظ</button></div>
    </form>
</x-layouts.admin>

<x-layouts.admin :title="$piece->exists ? $piece->name : 'قطعة جديدة'">
    <div class="flex flex-col gap-1"><a href="{{ route('admin.pieces.index') }}" class="text-sm">← المخزون</a><h1 class="m-0 font-display text-[2rem] font-bold">{{ $piece->exists ? $piece->name : 'قطعة جديدة' }}</h1></div>
    @if (session('label') && ! $piece->exists)
        <a href="{{ route('admin.pieces.labels', ['ids' => session('label')]) }}" target="_blank" class="btn btn-sm btn-outline self-start"><x-icon name="printer" :size="17" />اطبع تيكت القطعة اللي لسه ضايفها</a>
    @endif
    <form method="POST" action="{{ $piece->exists ? route('admin.pieces.update', $piece) : route('admin.pieces.store') }}" class="grid items-start gap-5 lg:grid-cols-[1fr_320px]">
        @csrf @if ($piece->exists) @method('PUT') @endif
        <section class="card grid gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="name" class="label">البيان</label><input id="name" name="name" value="{{ old('name', $piece->name) }}" required maxlength="120" placeholder="مثلاً: خاتم سوليتير" class="field" autofocus></div>
            <div><label for="category" class="label">النوع</label><select id="category" name="category" class="field">@foreach (\App\Enums\Category::cases() as $c)<option value="{{ $c->value }}" @selected(old('category', $piece->category?->value) === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="karat" class="label">العيار</label><select id="karat" name="karat" class="field">@foreach ([24, 21, 18] as $k)<option value="{{ $k }}" @selected((int) old('karat', $piece->karat) === $k)>{{ $k }}</option>@endforeach</select></div>
            <div><label for="weight" class="label">الوزن (جم)</label><input id="weight" name="weight_g" type="number" step="0.001" min="0.001" value="{{ old('weight_g', $piece->weight_g) }}" required class="field" dir="ltr"></div>
            <div><label for="making" class="label">المصنعية للقطعة (ج.م)</label><input id="making" name="making_fee" type="number" step="1" min="0" value="{{ old('making_fee', $piece->making_fee ?? 0) }}" required class="field" dir="ltr"></div>
            <div><label for="cost" class="label">التكلفة على المحل (اختياري)</label><input id="cost" name="cost" type="number" step="1" min="0" value="{{ old('cost', $piece->cost) }}" class="field" dir="ltr"><p class="m-0 mt-1 text-xs text-muted">منها بيتحسب المكسب في التقارير.</p></div>
            <div><label for="barcode" class="label">الباركود</label><input id="barcode" name="barcode" value="{{ old('barcode', $piece->barcode) }}" maxlength="40" class="field font-mono" dir="ltr" placeholder="سيبه فاضي ويتعمل لوحده"><p class="m-0 mt-1 text-xs text-muted">لو القطعة عليها تيكت بباركود، امسحه هنا.</p></div>
            <div class="sm:col-span-2"><label for="notes" class="label">ملاحظات</label><textarea id="notes" name="notes" rows="2" maxlength="500" class="field py-2">{{ old('notes', $piece->notes) }}</textarea></div>
        </section>
        <aside class="flex flex-col gap-3">
            @if ($piece->exists)
                <section class="card flex flex-col items-center gap-2 p-5">
                    <div class="w-full">{!! \App\Support\Code128::svg($piece->barcode, 50) !!}</div>
                    <span class="font-mono text-sm" dir="ltr">{{ $piece->barcode }}</span>
                    <a href="{{ route('admin.pieces.labels', ['ids' => $piece->id]) }}" target="_blank" class="btn btn-sm btn-outline"><x-icon name="printer" :size="17" />اطبع التيكت</a>
                </section>
                <button class="btn btn-lg btn-gold">احفظ</button>
            @else
                <button class="btn btn-lg btn-gold" name="after" value="list">احفظ</button>
                <button class="btn btn-lg btn-outline" name="after" value="another">احفظ وضيف قطعة تانية</button>
            @endif
        </aside>
    </form>
    @if ($piece->exists && ! $piece->lines_count)
        <form method="POST" action="{{ route('admin.pieces.destroy', $piece) }}" onsubmit="return confirm('تمسح القطعة دي من المخزون؟')">@csrf @method('DELETE')<button class="flex items-center gap-1 text-sm font-semibold text-down"><x-icon name="trash" :size="16" />امسح القطعة</button></form>
    @endif
</x-layouts.admin>

@php
    $productData = $products->mapWithKeys(fn ($p) => [$p->id => ['weight' => (float) $p->weight_g, 'making' => (float) $p->making_fee]]);
    $selected = old('product_id', $piece->product_id);
@endphp
<x-layouts.admin :title="$piece->exists ? 'قطعة '.$piece->barcode : 'تسجيل قطعة'">
    <div class="flex flex-col gap-1">
        <a href="{{ $piece->product_id ? route('admin.products.edit', $piece->product) : route('admin.products.index') }}" class="text-sm">← {{ $piece->product?->name ?? 'المنتجات والمخزون' }}</a>
        <h1 class="m-0 font-display text-[2rem] font-bold">{{ $piece->exists ? 'قطعة '.$piece->barcode : 'تسجيل قطعة' }}</h1>
        <p class="m-0 text-sm text-muted">امسح التيكت اللي على القطعة، واختار المنتج اللي هي تبعه. مفيش قطعة بتتسجل من غير كودها.</p>
    </div>
    <form method="POST" action="{{ $piece->exists ? route('admin.pieces.update', $piece) : route('admin.pieces.store') }}" class="grid items-start gap-5 lg:grid-cols-[1fr_320px]"
          x-data="{ product: @js((string) $selected), data: @js($productData), weight: @js((string) old('weight_g', $piece->weight_g)), making: @js((string) old('making_fee', $piece->making_fee ?? '')),
                    pick() { const d = this.data[this.product]; if (d) { this.weight = String(d.weight); this.making = String(d.making); } } }">
        @csrf @if ($piece->exists) @method('PUT') @endif
        <section class="card grid gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="barcode" class="label">كود القطعة</label>
                <input id="barcode" name="barcode" value="{{ old('barcode', $piece->barcode) }}" required maxlength="60" class="field text-lg" dir="ltr" autocomplete="off" @unless ($piece->exists) autofocus @endunless placeholder="امسح التيكت" @keydown.enter.prevent="$refs.product.focus()">
                @error('barcode')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="product" class="label">المنتج</label>
                <select id="product" name="product_id" x-ref="product" x-model="product" @change="pick()" required class="field">
                    <option value="">اختار المنتج…</option>
                    <option value="new">+ منتج جديد</option>
                    @foreach ($products->groupBy(fn ($p) => $p->category->label()) as $label => $group)
                        <optgroup label="{{ $label }}">
                            @foreach ($group as $p)<option value="{{ $p->id }}">{{ $p->name }} · عيار {{ $p->karat }}{{ $p->sku ? ' · '.$p->sku : '' }}</option>@endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('product_id')<p class="error">{{ $message }}</p>@enderror
            </div>
            <template x-if="product === 'new'">
                <div class="grid gap-5 rounded-2xl bg-paper p-4 sm:col-span-2 sm:grid-cols-3">
                    <div class="sm:col-span-3"><label for="new_name" class="label">اسم المنتج الجديد</label><input id="new_name" name="new_name" value="{{ old('new_name') }}" required maxlength="120" class="field"><p class="m-0 mt-1 text-xs text-muted">هيتعمل مخفي من الموقع لحد ما تضيفله صورة وتظهره.</p></div>
                    <div><label for="new_category" class="label">النوع</label><select id="new_category" name="new_category" class="field">@foreach (\App\Enums\Category::cases() as $c)<option value="{{ $c->value }}" @selected(old('new_category') === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
                    <div><label for="new_karat" class="label">العيار</label><select id="new_karat" name="new_karat" class="field">@foreach ([21, 18, 24] as $k)<option value="{{ $k }}" @selected((int) old('new_karat') === $k)>{{ $k }}</option>@endforeach</select></div>
                </div>
            </template>
            <div><label for="weight" class="label">الوزن (جم)</label><input id="weight" name="weight_g" type="number" step="0.001" min="0.001" x-model="weight" required class="field" dir="ltr"></div>
            <div><label for="making" class="label">المصنعية للقطعة (ج.م)</label><input id="making" name="making_fee" type="number" step="1" min="0" x-model="making" required class="field" dir="ltr"></div>
            <div><label for="cost" class="label">التكلفة على المحل (اختياري)</label><input id="cost" name="cost" type="number" step="1" min="0" value="{{ old('cost', $piece->cost) }}" class="field" dir="ltr"><p class="m-0 mt-1 text-xs text-muted">منها بيتحسب المكسب في التقارير.</p></div>
            @if ($branches->count() > 1)
                <div><label for="branch" class="label">الفرع</label><select id="branch" name="branch_id" class="field">@foreach ($branches as $b)<option value="{{ $b->id }}" @selected((int) old('branch_id', $piece->branch_id) === $b->id)>{{ $b->fullName() }}</option>@endforeach</select></div>
            @endif
            <div class="sm:col-span-2"><label for="notes" class="label">ملاحظات</label><textarea id="notes" name="notes" rows="2" maxlength="500" class="field py-2">{{ old('notes', $piece->notes) }}</textarea></div>
        </section>
        <aside class="flex flex-col gap-3">
            @if ($piece->exists)
                <section class="card flex flex-col items-center gap-2 p-5">
                    <div class="w-full">{!! \App\Support\Code128::svg($piece->barcode, 50) !!}</div>
                    <span class="font-mono text-sm" dir="ltr">{{ $piece->barcode }}</span>
                    <a href="{{ route('admin.pieces.labels', ['ids' => $piece->id]) }}" target="_blank" class="btn btn-sm btn-outline"><x-icon name="printer" :size="17" />اطبع تيكت بديلة</a>
                </section>
                <button class="btn btn-lg btn-gold">احفظ</button>
            @else
                <button class="btn btn-lg btn-gold" name="after" value="another">سجّل وامسح القطعة اللي بعدها</button>
                <button class="btn btn-lg btn-outline" name="after" value="product">سجّل وارجع للمنتج</button>
            @endif
        </aside>
    </form>
    @if ($piece->exists && ! $piece->lines_count)
        <form method="POST" action="{{ route('admin.pieces.destroy', $piece) }}" onsubmit="return confirm('تمسح القطعة دي من المخزون؟')">@csrf @method('DELETE')<button class="flex items-center gap-1 text-sm font-semibold text-down"><x-icon name="trash" :size="16" />امسح القطعة</button></form>
    @endif
</x-layouts.admin>

<x-layouts.admin :title="$product->exists ? $product->name : 'منتج جديد'">
    <div class="flex flex-col gap-1"><a href="{{ route('admin.products.index') }}" class="text-sm">← المنتجات</a><h1 class="m-0 font-display text-[2rem] font-bold">{{ $product->exists ? $product->name : 'منتج جديد' }}</h1></div>
    <form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="grid gap-5 lg:grid-cols-[1fr_340px]">
        @csrf @if ($product->exists) @method('PUT') @endif
        <section class="card grid gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="name" class="label">الاسم</label><input id="name" name="name" value="{{ old('name', $product->name) }}" required class="field"></div>
            <div><label for="category" class="label">النوع</label><select id="category" name="category" class="field">@foreach (\App\Enums\Category::cases() as $c)<option value="{{ $c->value }}" @selected(old('category', $product->category?->value) === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="karat" class="label">العيار</label><select id="karat" name="karat" class="field">@foreach ([24, 21, 18] as $k)<option value="{{ $k }}" @selected((int) old('karat', $product->karat) === $k)>{{ $k }}</option>@endforeach</select></div>
            <div><label for="weight" class="label">الوزن (جم)</label><input id="weight" name="weight_g" type="number" step="0.001" min="0.1" value="{{ old('weight_g', $product->weight_g) }}" required class="field" dir="ltr"></div>
            <div><label for="making" class="label">المصنعية للقطعة (ج.م)</label><input id="making" name="making_fee" type="number" step="1" min="0" value="{{ old('making_fee', $product->making_fee ?? 0) }}" required class="field" dir="ltr"></div>
            <div><label for="sku" class="label">الكود</label><input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" class="field" dir="ltr"></div>
            <div><label for="subtitle" class="label">تفاصيل قصيرة</label><input id="subtitle" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}" placeholder="مثلاً: طول 45 سم" class="field"></div>
            <div class="sm:col-span-2"><label for="desc" class="label">الوصف</label><textarea id="desc" name="description" rows="4" class="field py-3">{{ old('description', $product->description) }}</textarea></div>
            <div><label for="sort" class="label">الترتيب</label><input id="sort" name="sort" type="number" min="0" value="{{ old('sort', $product->sort ?? 0) }}" class="field"></div>
            <div class="flex flex-col justify-end gap-2">
                <label class="flex items-center gap-2"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" class="size-[18px] accent-gold-deep" @checked(old('is_published', $product->is_published))>ظاهر في الموقع والتطبيق</label>
                <label class="flex items-center gap-2"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" class="size-[18px] accent-gold-deep" @checked(old('is_featured', $product->is_featured))>يظهر في الرئيسية</label>
            </div>
        </section>
        <aside class="flex flex-col gap-5">
            <section class="card flex flex-col gap-3 p-6">
                <h2 class="m-0 text-lg font-bold">الصورة</h2>
                <span class="pedestal flex aspect-square items-center justify-center rounded-2xl"><img src="{{ $product->imageSrc() }}" alt="" class="h-[80%] w-[80%] object-contain"></span>
                <label for="image" class="label">صورة جديدة</label><input id="image" name="image" type="file" accept="image/*" class="text-sm">
            </section>
            <section class="card flex flex-col gap-3 p-6">
                <h2 class="m-0 text-lg font-bold">المخزون في الفروع</h2>
                @foreach ($branches as $b)
                    <div class="flex items-center justify-between gap-3"><label for="stock-{{ $b->id }}">{{ $b->fullName() }}</label><input id="stock-{{ $b->id }}" name="stock[{{ $b->id }}]" type="number" min="0" value="{{ old('stock.'.$b->id, (int) optional($product->branches?->firstWhere('id', $b->id))->pivot?->quantity) }}" class="field w-24 min-h-10"></div>
                @endforeach
            </section>
            @if ($product->exists)
                <p class="m-0 text-sm text-muted">السعر النهارده: <b class="text-text">{{ number_format($product->price()) }} ج.م</b></p>
            @endif
            <button class="btn btn-lg btn-gold">احفظ</button>
        </aside>
    </form>
    @if ($product->exists && $product->is_published)
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}">@csrf @method('DELETE')<button class="text-sm font-semibold text-down">اخفي المنتج</button></form>
    @endif
</x-layouts.admin>

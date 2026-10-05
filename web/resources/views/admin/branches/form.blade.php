<x-layouts.admin :title="$branch->exists ? $branch->name : 'فرع جديد'">
    <div class="flex flex-col gap-1"><a href="{{ route('admin.branches.index') }}" class="text-sm">← الفروع</a><h1 class="m-0 font-display text-[2rem] font-bold">{{ $branch->exists ? $branch->fullName() : 'فرع جديد' }}</h1></div>
    <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="card grid max-w-[860px] gap-5 p-6 sm:grid-cols-2">
        @csrf @if ($branch->exists) @method('PUT') @endif
        <div><label for="name" class="label">الاسم</label><input id="name" name="name" value="{{ old('name', $branch->name) }}" required class="field"></div>
        <div><label for="area" class="label">المنطقة</label><input id="area" name="area" value="{{ old('area', $branch->area) }}" class="field"></div>
        <div class="sm:col-span-2"><label for="address" class="label">العنوان</label><input id="address" name="address" value="{{ old('address', $branch->address) }}" class="field"></div>
        <div><label for="phone" class="label">التليفون</label><input id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" class="field" dir="ltr"></div>
        <div><label for="map" class="label">رابط الخريطة</label><input id="map" name="map_url" type="url" value="{{ old('map_url', $branch->map_url) }}" class="field" dir="ltr"></div>
        <div><label for="opens" class="label">بيفتح</label><input id="opens" name="opens_at" type="time" value="{{ old('opens_at', substr((string) $branch->opens_at, 0, 5)) }}" class="field"></div>
        <div><label for="closes" class="label">بيقفل</label><input id="closes" name="closes_at" type="time" value="{{ old('closes_at', substr((string) $branch->closes_at, 0, 5)) }}" class="field"></div>
        <div><label for="sort" class="label">الترتيب</label><input id="sort" name="sort" type="number" min="0" value="{{ old('sort', $branch->sort ?? 0) }}" class="field"></div>
        <label class="flex items-center gap-2 self-end"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="size-[18px] accent-gold-deep" @checked(old('is_active', $branch->is_active))>الفرع شغال وبيظهر للعملاء</label>
        <div class="sm:col-span-2"><button class="btn btn-lg btn-gold">احفظ</button></div>
    </form>
</x-layouts.admin>

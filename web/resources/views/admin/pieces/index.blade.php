<x-layouts.admin title="المخزون">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h1 class="m-0 font-display text-[2rem] font-bold">المخزون</h1><p class="m-0 text-sm text-muted">كل قطعة في المحل بباركود. القيمة محسوبة بسعر البيع النهارده.</p></div>
        <a href="{{ route('admin.pieces.create') }}" class="btn btn-gold"><x-icon name="plus" :size="18" />قطعة جديدة</a>
    </div>

    <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(190px,1fr))]">
        <div class="card-ink flex flex-col gap-1 rounded-3xl p-5"><span class="text-sm text-muted-dark">في المخزون</span><b class="text-3xl">{{ $stock['n'] }} <span class="text-[15px] font-normal text-muted-dark">قطعة</span></b><span class="text-[13px] text-gold-bright">{{ rtrim(rtrim(number_format($stock['weight'], 2), '0'), '.') }} جم · {{ number_format($stock['value']) }} ج.م</span></div>
        @foreach ($stock['rows'] as $row)
            <a href="{{ route('admin.pieces.index', ['karat' => $row['karat']]) }}" class="card flex flex-col gap-1 p-5 no-underline text-text"><span class="text-sm text-muted">عيار {{ $row['karat'] }}</span><b class="text-2xl">{{ rtrim(rtrim(number_format($row['weight'], 2), '0'), '.') }} <span class="text-[14px] font-normal text-muted">جم</span></b><span class="text-[13px] text-muted">{{ $row['n'] }} قطعة · {{ number_format($row['value']) }} ج.م</span></a>
        @endforeach
    </div>

    <form method="GET" class="card flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-[200px] flex-1"><label for="q" class="label">باركود أو اسم</label><input id="q" name="q" value="{{ request('q') }}" class="field"></div>
        <div><label for="status" class="label">الحالة</label><select id="status" name="status" class="field"><option value="all" @selected(! $status)>الكل</option>@foreach (\App\Enums\PieceStatus::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
        <div><label for="karat" class="label">العيار</label><select id="karat" name="karat" class="field"><option value="">الكل</option>@foreach ([24, 21, 18] as $k)<option value="{{ $k }}" @selected($karat === $k)>{{ $k }}</option>@endforeach</select></div>
        <div><label for="category" class="label">النوع</label><select id="category" name="category" class="field"><option value="">الكل</option>@foreach (\App\Enums\Category::cases() as $c)<option value="{{ $c->value }}" @selected($category === $c)>{{ $c->label() }}</option>@endforeach</select></div>
        <button class="btn btn-ink"><x-icon name="search" :size="18" />بحث</button>
    </form>

    <form method="GET" action="{{ route('admin.pieces.labels') }}" target="_blank" x-data="{ ids: @js(session('label') ? [(string) session('label')] : []) }" @submit="$refs.ids.value = ids.join(',')">
        <input type="hidden" name="ids" x-ref="ids">
        <section class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                <span class="text-sm text-muted"><b class="text-text" x-text="ids.length"></b> قطعة متعلّم عليها</span>
                <button class="btn btn-sm btn-outline" :disabled="!ids.length"><x-icon name="printer" :size="17" />اطبع التيكت</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] border-collapse text-sm">
                    <thead><tr class="bg-paper text-[13px] text-muted">
                        <th scope="col" class="w-10 px-4 py-3"><span class="sr-only">اختار</span></th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">الباركود</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">البيان</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">العيار</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">الوزن (جم)</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">المصنعية</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">التكلفة</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th scope="col"><span class="sr-only">تعديل</span></th>
                    </tr></thead>
                    <tbody>
                        @forelse ($pieces as $piece)
                            <tr class="border-t border-line hover:bg-paper">
                                <td class="px-4 py-3"><input type="checkbox" value="{{ $piece->id }}" x-model="ids" class="size-[18px] accent-gold-deep" aria-label="اختار {{ $piece->name }}"></td>
                                <td class="px-4 py-3 font-mono" dir="ltr">{{ $piece->barcode }}</td>
                                <td class="px-4 py-3"><b>{{ $piece->name }}</b><div class="text-xs text-muted">{{ $piece->category->label() }}</div></td>
                                <td class="px-4 py-3">{{ $piece->karat }}</td>
                                <td class="px-4 py-3" dir="ltr">{{ $piece->weight_g }}</td>
                                <td class="px-4 py-3">{{ number_format($piece->making_fee) }}</td>
                                <td class="px-4 py-3">{{ $piece->cost !== null ? number_format($piece->cost) : '—' }}</td>
                                <td class="px-4 py-3"><span class="pill pill-{{ $piece->status->tone() }}">{{ $piece->status->label() }}</span></td>
                                <td class="px-4 py-3">@if ($piece->isInStock())<a href="{{ route('admin.pieces.edit', $piece) }}" class="text-sm font-semibold">تعديل</a>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-10 text-center text-muted">مفيش قطع. <a href="{{ route('admin.pieces.create') }}">ضيف أول قطعة</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </form>
    {{ $pieces->links() }}
</x-layouts.admin>

<x-layouts.admin title="المنتجات والمخزون">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="m-0 font-display text-[2rem] font-bold">المنتجات والمخزون</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.pieces.create') }}" class="btn btn-outline"><x-icon name="barcode" :size="18" />سجّل قطعة بالكود</a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-gold"><x-icon name="plus" :size="18" />منتج جديد</a>
        </div>
    </div>
    <form method="GET" class="flex flex-wrap gap-2">
        <a href="{{ route('admin.products.index') }}" class="chip" @if (! $cat) aria-current="true" @endif>الكل</a>
        @foreach (\App\Enums\Category::cases() as $c)
            <a href="{{ route('admin.products.index', ['category' => $c->value]) }}" class="chip" @if ($cat === $c) aria-current="true" @endif>{{ $c->label() }}</a>
        @endforeach
    </form>
    <section class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] border-collapse text-sm">
                <thead><tr class="bg-paper text-[13px] text-muted"><th scope="col" class="px-4 py-3 text-start font-medium">المنتج</th><th scope="col" class="text-start font-medium">العيار · الوزن</th><th scope="col" class="text-start font-medium">المصنعية</th><th scope="col" class="text-start font-medium">السعر النهارده</th>
                    <th scope="col" class="text-start font-medium">قطع في المخزون</th>
                    <th scope="col" class="text-start font-medium">الظهور</th></tr></thead>
                <tbody>
                    @foreach ($products as $p)
                        <tr class="border-t border-line hover:bg-paper">
                            <td class="px-4 py-2.5"><a href="{{ route('admin.products.edit', $p) }}" class="flex items-center gap-3 text-text no-underline"><span class="pedestal flex size-12 flex-none items-center justify-center rounded-xl"><img src="{{ $p->imageSrc() }}" alt="" class="size-10 object-contain"></span><span class="flex flex-col"><b>{{ $p->name }}</b><span class="text-xs text-muted">{{ $p->category->label() }} · {{ $p->sku }}</span></span></a></td>
                            <td>{{ $p->karat }} · {{ $p->weightLabel() }}</td>
                            <td>{{ number_format((float) $p->making_fee) }}</td>
                            <td class="font-bold">{{ number_format($p->price()) }}</td>
                            @php $q = (int) $p->in_stock; @endphp
                            <td><span class="pill {{ $q === 0 ? 'pill-red' : ($q <= 1 ? 'pill-amber' : 'pill-green') }}">{{ $q }}</span></td>
                            <td><span class="pill {{ $p->is_published ? 'pill-green' : 'pill-neutral' }}">{{ $p->is_published ? 'ظاهر' : 'مخفي' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    {{ $products->links() }}
</x-layouts.admin>

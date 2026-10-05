<x-layouts.admin title="الفروع">
    <div class="flex flex-wrap items-end justify-between gap-3"><h1 class="m-0 font-display text-[2rem] font-bold">الفروع</h1><a href="{{ route('admin.branches.create') }}" class="btn btn-gold"><x-icon name="plus" :size="18" />فرع جديد</a></div>
    <div class="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(300px,1fr))]">
        @foreach ($branches as $b)
            <a href="{{ route('admin.branches.edit', $b) }}" class="lift card flex flex-col gap-2 p-5 text-text no-underline">
                <div class="flex items-center justify-between gap-2"><b class="font-display text-xl">{{ $b->fullName() }}</b><span class="pill {{ $b->is_active ? ($b->isOpen() ? 'pill-green' : 'pill-neutral') : 'pill-red' }}">{{ $b->is_active ? $b->statusLabel() : 'موقوف' }}</span></div>
                <span class="text-sm text-muted">{{ $b->address }}</span>
                <span class="text-sm text-muted">{{ $b->hoursLabel() }} · <span dir="ltr">{{ $b->phone }}</span></span>
            </a>
        @endforeach
    </div>
</x-layouts.admin>

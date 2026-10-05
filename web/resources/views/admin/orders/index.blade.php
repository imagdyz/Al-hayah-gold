<x-layouts.admin title="الطلبات والحجوزات">
    <h1 class="m-0 font-display text-[2rem] font-bold">الطلبات والحجوزات</h1>
    <nav aria-label="حالة الطلب" class="flex flex-wrap gap-2">
        <a href="{{ route('admin.orders.index', request()->except(['status', 'page'])) }}" class="chip" @if (! $status) aria-current="true" @endif>الكل <span class="opacity-60">{{ $counts->sum() }}</span></a>
        @foreach (\App\Enums\OrderStatus::cases() as $s)
            <a href="{{ route('admin.orders.index', ['status' => $s->value] + request()->except(['status', 'page'])) }}" class="chip gap-1.5" @if ($status === $s) aria-current="true" @endif>{{ $s->label() }} <span class="opacity-60">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <form method="GET" class="card flex flex-wrap items-end gap-3 p-4">
        @if ($status)<input type="hidden" name="status" value="{{ $status->value }}">@endif
        <div class="min-w-[200px] flex-1"><label for="q" class="label">بحث بالكود أو الموبايل</label><input id="q" name="q" value="{{ $q }}" class="field" dir="ltr"></div>
        <div><label for="type" class="label">النوع</label><select id="type" name="type" class="field"><option value="">الكل</option>@foreach (\App\Enums\OrderType::cases() as $t)<option value="{{ $t->value }}" @selected($type === $t)>{{ $t->label() }}</option>@endforeach</select></div>
        <div><label for="branch" class="label">الفرع</label><select id="branch" name="branch" class="field"><option value="">كل الفروع</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected($branch === $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <button class="btn btn-ink">اعرض</button>
    </form>
    <section class="card p-4">@include('admin.orders.table', ['orders' => $orders])</section>
    {{ $orders->links() }}
</x-layouts.admin>

<x-layouts.admin title="الفواتير">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="m-0 font-display text-[2rem] font-bold">الفواتير</h1>
        <a href="{{ route('admin.pos') }}" class="btn btn-gold"><x-icon name="plus" :size="18" />فاتورة جديدة</a>
    </div>
    <form method="GET" class="card flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-[200px] flex-1"><label for="q" class="label">رقم الفاتورة أو العميل</label><input id="q" name="q" value="{{ request('q') }}" class="field"></div>
        <div><label for="from" class="label">من</label><input id="from" name="from" type="date" value="{{ request('from') }}" class="field"></div>
        <div><label for="to" class="label">إلى</label><input id="to" name="to" type="date" value="{{ request('to') }}" class="field"></div>
        <div><label for="status" class="label">الحالة</label><select id="status" name="status" class="field"><option value="">الكل</option>@foreach (\App\Enums\InvoiceStatus::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
        <button class="btn btn-ink"><x-icon name="search" :size="18" />بحث</button>
    </form>
    <section class="card overflow-hidden"><div class="overflow-x-auto">
        <table class="w-full min-w-[860px] border-collapse text-sm">
            <thead><tr class="bg-paper text-[13px] text-muted">
                <th scope="col" class="px-4 py-3 text-start font-medium">الرقم</th>
                <th scope="col" class="px-4 py-3 text-start font-medium">التاريخ</th>
                <th scope="col" class="px-4 py-3 text-start font-medium">العميل</th>
                <th scope="col" class="px-4 py-3 text-start font-medium">بيع</th>
                <th scope="col" class="px-4 py-3 text-start font-medium">شراء</th>
                <th scope="col" class="px-4 py-3 text-start font-medium">الصافي</th>
                <th scope="col" class="px-4 py-3 text-start font-medium">التسوية</th>
                <th scope="col" class="px-4 py-3 text-start font-medium">الحالة</th>
            </tr></thead>
            <tbody>
                @forelse ($invoices as $inv)
                    <tr class="border-t border-line hover:bg-paper">
                        <td class="px-4 py-3"><a href="{{ route('admin.invoices.show', $inv) }}" class="font-bold" dir="ltr">{{ $inv->label() }}</a></td>
                        <td class="px-4 py-3">{{ $inv->issued_at->format('Y-m-d g:i a') }}</td>
                        <td class="px-4 py-3">{{ $inv->customer_name ?: '—' }}</td>
                        <td class="px-4 py-3">{{ number_format($inv->sales_total) }} <span class="text-muted">({{ $inv->sales_count }})</span></td>
                        <td class="px-4 py-3">{{ number_format($inv->purchases_total) }} <span class="text-muted">({{ $inv->purchases_count }})</span></td>
                        <td class="px-4 py-3 font-bold">{{ number_format(abs($inv->net)) }} <span class="text-xs font-normal text-muted">{{ $inv->netLabel() }}</span></td>
                        <td class="px-4 py-3">{{ $inv->settlement->label() }}</td>
                        <td class="px-4 py-3"><span class="pill pill-{{ $inv->status->tone() }}">{{ $inv->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-muted">مفيش فواتير.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></section>
    {{ $invoices->links() }}
</x-layouts.admin>

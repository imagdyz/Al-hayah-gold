<div class="overflow-x-auto">
    <table class="w-full min-w-[760px] border-collapse text-sm">
        <thead><tr class="text-[13px] text-muted"><th scope="col" class="px-2 py-2.5 text-start font-medium">الكود</th><th scope="col" class="text-start font-medium">النوع</th><th scope="col" class="text-start font-medium">التفاصيل</th><th scope="col" class="text-start font-medium">العميل</th><th scope="col" class="text-start font-medium">الفرع</th><th scope="col" class="text-start font-medium">المبلغ</th><th scope="col" class="text-start font-medium">الحالة</th></tr></thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="border-t border-[#F0ECE5] hover:bg-paper">
                    <td class="px-2 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold" dir="ltr">{{ $order->code }}</a></td>
                    <td>{{ $order->type->label() }}</td>
                    <td>{{ $order->title() }}</td>
                    <td dir="ltr" class="text-end">{{ \App\Services\Phone::mask($order->phone) }}</td>
                    <td>{{ $order->branch->name }}</td>
                    <td>{{ number_format((float) $order->total) }}</td>
                    <td><x-status-pill :status="$order->status" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-muted">مفيش طلبات.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

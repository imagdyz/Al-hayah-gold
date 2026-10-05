<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));
        $type = OrderType::tryFrom((string) $request->query('type'));
        $branch = $request->integer('branch') ?: null;
        $q = trim((string) $request->query('q'));

        $orders = Order::with(['product', 'branch'])
            ->when($status, fn ($w) => $w->where('status', $status->value))
            ->when($type, fn ($w) => $w->where('type', $type->value))
            ->when($branch, fn ($w) => $w->where('branch_id', $branch))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('code', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->latest()->paginate(20)->withQueryString();

        $counts = Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.orders.index', [
            'orders' => $orders,
            'counts' => $counts,
            'status' => $status,
            'type' => $type,
            'branch' => $branch,
            'q' => $q,
            'branches' => Branch::active()->get(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['product', 'branch', 'user']);

        return view('admin.orders.show', ['order' => $order]);
    }

    public function update(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)]]);
        $orders->setStatus($order, OrderStatus::from($data['status']));

        return back()->with('status', 'حالة الطلب بقت: '.$order->fresh()->status->label());
    }
}

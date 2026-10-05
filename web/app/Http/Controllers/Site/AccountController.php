<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with(['product', 'branch'])->latest()->get();

        return view('site.account', [
            'open' => $orders->filter(fn (Order $o) => $o->status->isOpen())->values(),
            'past' => $orders->reject(fn (Order $o) => $o->status->isOpen())->values(),
        ]);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->is_admin, 404);
        $order->load(['product', 'branch']);

        return view('site.orders.show', ['order' => $order]);
    }
}

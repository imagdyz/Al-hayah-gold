<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BullionController extends Controller
{
    public function index(Request $request)
    {
        $items = Product::published()->bullion()->with('branches')->orderBy('category')->orderBy('weight_g')->get();
        $selected = $items->firstWhere('slug', $request->query('item')) ?? $items->firstWhere('slug', 'bar10') ?? $items->first();

        return view('site.bullion', [
            'items' => $items,
            'selected' => $selected,
            'branches' => Branch::active()->get(),
            'lockMinutes' => Setting::int('price_lock_minutes'),
            'depositPercent' => Setting::int('deposit_percent'),
        ]);
    }

    public function store(Request $request, OrderService $orders)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_published', true)],
            'quantity' => ['required', 'integer', 'between:1,20'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'pay_method' => ['required', Rule::in(['branch', 'deposit'])],
        ]);

        $order = $orders->placeBullion(
            $request->user(),
            Product::findOrFail($data['product_id']),
            (int) $data['quantity'],
            Branch::findOrFail($data['branch_id']),
            $data['pay_method'],
        );

        return redirect()->route('orders.show', $order)->with('status', 'طلبك اتأكد وسعرك اتثبّت.');
    }
}

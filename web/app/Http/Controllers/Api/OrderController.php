<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\ReservationController;
use App\Http\Resources\OrderResource;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return OrderResource::collection($request->user()->orders()->with(['product', 'branch'])->latest()->get());
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return new OrderResource($order->load(['product', 'branch']));
    }

    public function store(Request $request, OrderService $orders)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['bullion', 'reservation', 'sell'])],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'product' => ['required_unless:type,sell', 'nullable', Rule::exists('products', 'slug')->where('is_published', true)],
            'quantity' => ['nullable', 'integer', 'between:1,20'],
            'pay_method' => ['nullable', Rule::in(['branch', 'deposit'])],
            'slot' => ['required_unless:type,bullion', 'nullable', Rule::in(array_keys(ReservationController::slots()))],
            'karat' => ['required_if:type,sell', 'nullable', Rule::in(['24', '21', '18'])],
            'grams' => ['required_if:type,sell', 'nullable', 'numeric', 'min:0.5', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $branch = Branch::findOrFail($data['branch_id']);
        $pay = $data['pay_method'] ?? 'branch';

        $order = match ($data['type']) {
            'bullion' => $orders->placeBullion($user, Product::where('slug', $data['product'])->firstOrFail(), (int) ($data['quantity'] ?? 1), $branch, $pay),
            'reservation' => $orders->placeReservation($user, Product::where('slug', $data['product'])->firstOrFail(), $branch, Carbon::parse($data['slot']), $pay),
            'sell' => $orders->bookSell($user, $branch, (int) $data['karat'], (float) $data['grams'], Carbon::parse($data['slot']), $data['notes'] ?? null),
        };

        return (new OrderResource($order->load(['product', 'branch'])))->response()->setStatusCode(201);
    }
}

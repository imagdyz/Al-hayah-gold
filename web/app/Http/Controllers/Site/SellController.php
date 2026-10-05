<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\GoldPricing;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SellController extends Controller
{
    public function create(GoldPricing $pricing)
    {
        return view('site.sell', [
            'buy' => ['24' => $pricing->buy('24'), '21' => $pricing->buy('21'), '18' => $pricing->buy('18')],
            'branches' => Branch::active()->get(),
            'slots' => ReservationController::slots(),
        ]);
    }

    public function store(Request $request, OrderService $orders)
    {
        $data = $request->validate([
            'karat' => ['required', Rule::in(['24', '21', '18'])],
            'grams' => ['required', 'numeric', 'min:0.5', 'max:2000'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'slot' => ['required', Rule::in(array_keys(ReservationController::slots()))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $orders->bookSell(
            $request->user(),
            Branch::findOrFail($data['branch_id']),
            (int) $data['karat'],
            (float) $data['grams'],
            Carbon::parse($data['slot']),
            $data['notes'] ?? null,
        );

        return redirect()->route('orders.show', $order)->with('status', 'معادك اتأكد.');
    }
}

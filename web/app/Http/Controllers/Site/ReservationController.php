<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function create(Product $product)
    {
        abort_unless($product->is_published, 404);
        if ($product->isBullion()) {
            return redirect()->route('bullion', ['item' => $product->slug]);
        }
        $product->load('branches');

        return view('site.reserve', [
            'product' => $product,
            'branches' => Branch::active()->get(),
            'slots' => self::slots(),
            'depositPercent' => Setting::int('deposit_percent'),
            'holdHours' => Setting::int('reservation_hours'),
        ]);
    }

    public function store(Request $request, Product $product, OrderService $orders)
    {
        abort_unless($product->is_published, 404);
        $data = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'slot' => ['required', Rule::in(array_keys(self::slots()))],
            'pay_method' => ['required', Rule::in(['branch', 'deposit'])],
        ]);

        $order = $orders->placeReservation(
            $request->user(),
            $product,
            Branch::findOrFail($data['branch_id']),
            Carbon::parse($data['slot']),
            $data['pay_method'],
        );

        return redirect()->route('orders.show', $order)->with('status', 'القطعة اتحجزت لك.');
    }

    /** Appointment slots for the next three days: value => label. */
    public static function slots(): array
    {
        $out = [];
        foreach ([0, 1, 2] as $d) {
            $day = now()->startOfDay()->addDays($d);
            foreach ([13, 17, 20] as $h) {
                $at = $day->copy()->setTime($h, 0);
                if ($at->lt(now()->addHour())) {
                    continue;
                }
                $dayLabel = match ($d) {
                    0 => 'النهارده', 1 => 'بكرة', default => $at->locale('ar')->translatedFormat('l')
                };
                $out[$at->format('Y-m-d H:i')] = $dayLabel.' · '.Branch::formatTime($at->format('H:i:s'));
            }
        }

        return $out;
    }
}

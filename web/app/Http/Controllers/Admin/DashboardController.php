<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $live = fn () => Order::query()->where('status', '!=', OrderStatus::Cancelled->value);

        $bullionGrams = fn ($from, $to) => (float) $live()->where('type', OrderType::Bullion->value)->whereBetween('created_at', [$from, $to])->sum('weight_g');
        $sellGrams = fn ($from, $to) => (float) $live()->where('type', OrderType::Sell->value)->whereBetween('created_at', [$from, $to])->sum('weight_g');

        $kpis = [
            'bullion' => [$bullionGrams($today, now()), $bullionGrams($yesterday, $today)],
            'sell' => [$sellGrams($today, now()), $sellGrams($yesterday, $today)],
            'reservations' => Order::open()->where('type', OrderType::Reservation->value)->count(),
            'ready' => Order::where('status', OrderStatus::Ready->value)->count(),
            'new' => Order::where('status', OrderStatus::New->value)->count(),
        ];

        // Grams of bullion ordered per day over the last 14 days.
        $rows = $live()->where('type', OrderType::Bullion->value)
            ->where('created_at', '>=', $today->copy()->subDays(13))
            ->get(['created_at', 'weight_g'])
            ->groupBy(fn ($o) => $o->created_at->toDateString())
            ->map(fn ($g) => round((float) $g->sum('weight_g'), 1));
        $daily = collect(range(13, 0))->map(function ($d) use ($today, $rows) {
            $day = $today->copy()->subDays($d);

            return ['label' => $day->locale('ar')->translatedFormat('j M'), 'v' => $rows[$day->toDateString()] ?? 0];
        });

        $byBranch = Branch::active()->get()->map(fn (Branch $b) => [
            'name' => $b->fullName(),
            'orders' => $live()->where('branch_id', $b->id)->where('created_at', '>=', $today->copy()->subDays(13))->count(),
        ]);

        $lowStock = DB::table('branch_product')
            ->join('products', 'products.id', '=', 'branch_product.product_id')
            ->join('branches', 'branches.id', '=', 'branch_product.branch_id')
            ->where('products.is_published', true)->where('branch_product.quantity', '<=', 1)
            ->orderBy('branch_product.quantity')->limit(6)
            ->get(['products.name', 'branches.name as branch', 'branch_product.quantity']);

        return view('admin.dashboard', [
            'kpis' => $kpis,
            'daily' => $daily,
            'byBranch' => $byBranch,
            'lowStock' => $lowStock,
            'latest' => Order::with(['product', 'branch'])->latest()->take(8)->get(),
            'productCount' => Product::published()->count(),
        ]);
    }
}

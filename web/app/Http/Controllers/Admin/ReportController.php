<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PosReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request, PosReport $report)
    {
        $from = $this->date($request->query('from')) ?? today();
        $to = $this->date($request->query('to')) ?? today();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'r' => $report->summary($from->copy()->startOfDay(), $to->copy()->endOfDay()),
            'stock' => $report->stock(),
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            return is_string($value) && $value !== '' ? Carbon::parse($value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

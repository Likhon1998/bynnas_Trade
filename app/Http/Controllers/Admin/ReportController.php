<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return view('admin.reports.index', [
            'catalog' => $this->reports->catalog(),
        ]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return view('admin.reports.show', [
            'key' => $report,
            'report' => $this->reports->generate($report, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'catalog' => $this->reports->catalog(),
        ]);
    }

    public function export(Request $request, string $report)
    {
        abort_unless($request->user()->can('reports.export') || $request->user()->can('reports.view'), 403);

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return $this->reports->csv($report, $from, $to);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);

        [$from, $to] = $this->period($request);

        return view('admin.reports.index', [
            'catalog' => $this->reports->catalog(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless($request->user()->can('reports.view'), 403);

        [$from, $to] = $this->period($request);

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
        abort_unless($request->user()->can('reports.export'), 403);

        [$from, $to] = $this->period($request);

        return $this->reports->csv($report, $from, $to);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            return [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}

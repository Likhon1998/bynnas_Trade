<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('analytics.view'), 403);

        return view('admin.analytics.index', $this->analytics->analyticsPage());
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function __invoke(Request $request)
    {
        $data = $this->analytics->dashboard();

        return view('admin.dashboard', $data);
    }
}

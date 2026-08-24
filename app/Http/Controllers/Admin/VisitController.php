<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopVisit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ShopVisit::class);

        $visits = ShopVisit::query()
            ->with(['shop', 'salesman', 'order'])
            ->when($request->salesman_id, fn ($q, $id) => $q->where('salesman_id', $id))
            ->when($request->shop_id, fn ($q, $id) => $q->where('shop_id', $id))
            ->when($request->outcome, fn ($q, $outcome) => $q->where('outcome', $outcome))
            ->latest('checked_in_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.visits.index', compact('visits'));
    }
}

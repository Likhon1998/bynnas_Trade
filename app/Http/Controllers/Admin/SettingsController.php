<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('settings.view'), 403);

        return view('admin.settings.index', [
            'commissionRule' => CommissionRule::activeDefault(),
            'defaultWarehouse' => Warehouse::defaultWarehouse(),
            'warehouseCount' => Warehouse::query()->where('is_active', true)->count(),
            'defaultPriceGroup' => PriceGroup::query()->where('is_active', true)->where('is_default', true)->first(),
            'priceGroupCount' => PriceGroup::query()->where('is_active', true)->count(),
            'whatsappDriver' => config('whatsapp.driver', 'log'),
            'mailDriver' => config('mail.default'),
            'timezone' => config('app.timezone'),
        ]);
    }
}

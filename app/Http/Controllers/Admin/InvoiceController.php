<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('invoices.view'), 403);

        $invoices = Invoice::query()
            ->with(['shop', 'order'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhereHas('shop', fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('order', fn ($o) => $o->where('number', 'like', "%{$search}%"));
                });
            })
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        abort_unless($request->user()->can('invoices.view'), 403);

        $invoice->load(['shop', 'order', 'items', 'payments', 'creator']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function storeFromOrder(Request $request, Order $order)
    {
        abort_unless($request->user()->can('invoices.create'), 403);

        $invoice = $this->invoices->createFromOrder($order, $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice '.$invoice->number.' issued. Shop credit updated.');
    }
}

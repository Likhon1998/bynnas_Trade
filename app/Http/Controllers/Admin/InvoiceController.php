<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('invoices.view'), 403);

        $invoices = Invoice::query()
            ->with(['shop', 'order'])
            ->latest('issued_at')
            ->limit(500)
            ->get();

        $rows = $invoices->map(function (Invoice $invoice) {
            $kind = $this->documentKind($invoice);

            return [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'shop' => $invoice->shop?->name ?: '',
                'shop_code' => $invoice->shop?->code ?: '',
                'order' => $invoice->order?->number ?: '',
                'issued_at' => $invoice->issued_at?->format('d M Y') ?: '—',
                'due_at' => $invoice->due_at?->format('d M Y') ?: '—',
                'total' => \App\Support\DemoData::taka($invoice->total),
                'balance' => \App\Support\DemoData::taka($invoice->balance),
                'balance_raw' => (float) $invoice->balance,
                'status' => $invoice->status,
                'status_label' => $invoice->statusLabel(),
                'kind' => $kind,
                'show_url' => route('invoices.show', $invoice, false),
                'download_url' => route('invoices.download', $invoice, false),
                'preview_url' => route('invoices.show', $invoice, false).'#preview',
            ];
        })->values();

        $initialFilters = [
            'search' => (string) $request->get('search', ''),
            'status' => (string) $request->get('status', ''),
        ];

        return view('admin.invoices.index', compact('rows', 'initialFilters'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        abort_unless($request->user()->can('invoices.view'), 403);

        $invoice->load([
            'shop',
            'order.advanceInvoice',
            'order.invoice',
            'items',
            'payments' => fn ($q) => $q->orderBy('paid_at')->orderBy('id'),
            'creator',
        ]);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function download(Request $request, Invoice $invoice)
    {
        abort_unless($request->user()->can('invoices.view'), 403);

        $invoice->load([
            'shop',
            'order.advanceInvoice',
            'order.invoice',
            'items',
            'payments' => fn ($q) => $q->orderBy('paid_at')->orderBy('id'),
            'creator',
        ]);

        $documentKind = $this->documentKind($invoice);
        $filename = str_replace(['/', '\\', ' '], '-', $invoice->number).'.pdf';

        return Pdf::loadView('admin.invoices.document', [
            'invoice' => $invoice,
            'documentKind' => $documentKind,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($filename);
    }

    public function storeFromOrder(Request $request, Order $order)
    {
        abort_unless($request->user()->can('invoices.create'), 403);

        $invoice = $this->invoices->createFromOrder($order, $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice '.$invoice->number.' issued. Shop credit updated.');
    }

    private function documentKind(Invoice $invoice): string
    {
        $notes = (string) $invoice->notes;
        if (str_contains(strtolower($notes), 'advance')) {
            return 'Advance Invoice';
        }

        if ($invoice->order && (int) $invoice->order->advance_invoice_id === (int) $invoice->id) {
            return 'Advance Invoice';
        }

        return 'Sales Invoice';
    }
}

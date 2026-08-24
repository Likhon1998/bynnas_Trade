<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Shop;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('payments.view'), 403);

        $payments = Payment::query()
            ->with(['shop', 'invoice', 'verifier'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('shop', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'shops' => Shop::query()->where('status', '!=', Shop::STATUS_REJECTED)->orderBy('name')->get(),
            'openInvoices' => Invoice::query()->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])->with('shop')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('payments.create'), 403);

        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank_transfer,cheque,mobile_banking'],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'verify_now' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['invoice_id'])) {
            $invoice = Invoice::query()->findOrFail($data['invoice_id']);
            $data['shop_id'] = $invoice->shop_id;
        }

        $payment = $this->payments->record($data, $request->user());

        if ($request->boolean('verify_now') && $request->user()->can('payments.verify')) {
            $this->payments->verify($payment, $request->user());

            return back()->with('success', 'Payment recorded and verified. Credit updated.');
        }

        return back()->with('success', 'Payment recorded — pending verification.');
    }

    public function verify(Request $request, Payment $payment)
    {
        abort_unless($request->user()->can('payments.verify'), 403);
        $this->payments->verify($payment, $request->user());

        return back()->with('success', 'Payment verified. Shop outstanding reduced.');
    }

    public function reject(Request $request, Payment $payment)
    {
        abort_unless($request->user()->can('payments.verify'), 403);

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $this->payments->reject($payment, $data['rejection_reason'], $request->user());

        return back()->with('success', 'Payment rejected.');
    }
}

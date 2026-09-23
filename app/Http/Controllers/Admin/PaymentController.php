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

        return view('admin.payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('payments.create'), 403);

        return view('admin.payments.create', $this->formData($request));
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

        $downloadUrl = $payment->invoice_id
            ? route('invoices.download', $payment->invoice_id)
            : null;

        if ($request->boolean('verify_now') && $request->user()->can('payments.verify')) {
            if ($payment->status !== Payment::STATUS_VERIFIED) {
                $this->payments->verify($payment, $request->user());
            }

            return redirect()->route('payments.index')
                ->with('success', 'Payment recorded and verified. Related orders updated automatically.')
                ->with('invoice_download', $downloadUrl);
        }

        if ($payment->status === Payment::STATUS_VERIFIED) {
            return redirect()->route('payments.index')
                ->with('success', 'Advance payment applied — order status updated automatically.')
                ->with('invoice_download', $downloadUrl);
        }

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded — pending verification.')
            ->with('invoice_download', $downloadUrl);
    }

    public function verify(Request $request, Payment $payment)
    {
        abort_unless($request->user()->can('payments.verify'), 403);
        $this->payments->verify($payment, $request->user());

        return back()
            ->with('success', 'Payment verified. Shop outstanding reduced.')
            ->with('invoice_download', $payment->invoice_id ? route('invoices.download', $payment->invoice_id) : null);
    }

    public function reject(Request $request, Payment $payment)
    {
        abort_unless($request->user()->can('payments.verify'), 403);

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $this->payments->reject($payment, $data['rejection_reason'], $request->user());

        return back()->with('success', 'Payment rejected.');
    }

    /**
     * @return array{shops: \Illuminate\Support\Collection, openInvoices: \Illuminate\Support\Collection}
     */
    private function formData(Request $request): array
    {
        return [
            'shops' => Shop::query()
                ->where('status', '!=', Shop::STATUS_REJECTED)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'outstanding_balance']),
            'openInvoices' => Invoice::query()
                ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])
                ->with(['shop:id,name', 'order:id,number,advance_invoice_id'])
                ->latest()
                ->get(),
            'prefillInvoiceId' => $request->integer('invoice_id') ?: null,
            'prefillShopId' => $request->integer('shop_id') ?: null,
        ];
    }
}

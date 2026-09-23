<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $pendingCount = Order::query()
            ->whereIn('status', [Order::STATUS_PENDING_AUDIT, Order::STATUS_AWAITING_ADVANCE])
            ->count();

        $orders = Order::query()
            ->with(['shop', 'salesman', 'auditor', 'items.product', 'advanceInvoice'])
            ->latest('submitted_at')
            ->limit(500)
            ->get();

        $user = $request->user();

        $rows = $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'number' => $order->number,
            'shop' => $order->shop?->name ?: '',
            'shop_code' => $order->shop?->code ?: '',
            'source' => $order->source,
            'source_label' => ucfirst(str_replace('_', ' ', $order->source)),
            'salesman' => $order->salesman?->name ?: '—',
            'item_count' => $order->item_count,
            'total' => \App\Support\DemoData::taka($order->total),
            'submitted_at' => $order->submitted_at?->format('d M Y H:i') ?: '—',
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'status_badge' => $this->statusBadgeClass($order),
            'advance_paid' => $order->isAwaitingAdvance() && $order->isAdvancePaid(),
        ])->values();

        $previews = $orders->mapWithKeys(
            fn (Order $order) => [$order->id => $this->previewPayload($order, $user)]
        );

        $initialFilters = [
            'search' => (string) $request->get('search', ''),
            'status' => $request->boolean('audit_queue')
                ? Order::STATUS_PENDING_AUDIT
                : (string) $request->get('status', ''),
            'source' => (string) $request->get('source', ''),
        ];

        return view('admin.orders.index', compact('rows', 'pendingCount', 'previews', 'initialFilters'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Order::class);

        return view('admin.orders.create', [
            'shops' => Shop::query()
                ->with(['priceGroup', 'assignedSalesman'])
                ->where('status', Shop::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
            'products' => Product::query()
                ->with('prices')
                ->where('status', Product::STATUS_ACTIVE)
                ->where('is_published', true)
                ->orderBy('name')
                ->get(),
            'salesmen' => User::query()
                ->where('portal', User::PORTAL_SALESMAN)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'salesman_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $shop = Shop::query()->findOrFail($data['shop_id']);

        if ($shop->status !== Shop::STATUS_ACTIVE) {
            return back()
                ->withInput()
                ->withErrors(['shop_id' => 'Orders can only be placed for active shops.']);
        }

        $order = $this->orders->createFromAdmin(
            $request->user(),
            $shop,
            $data['items'],
            $data['notes'] ?? null,
            $data['salesman_id'] ?? null,
        );

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order '.$order->number.' created for '.$shop->name.' and submitted for Super Admin audit.');
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'shop.priceGroup', 'salesman', 'visit', 'items.product',
            'creator', 'auditor', 'canceller', 'statusHistories.user',
            'warehouse', 'fulfilment.delivery', 'invoice',
        ]);

        $snapshot = $this->orders->auditSnapshot($order);

        return view('admin.orders.show', compact('order', 'snapshot'));
    }

    public function preview(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['shop', 'salesman', 'items.product', 'auditor']);

        return response()->json($this->previewPayload($order, request()->user()));
    }

    public function approve(Request $request, Order $order)
    {
        $this->authorize('approve', $order);

        $data = $request->validate([
            'audit_notes' => ['nullable', 'string', 'max:2000'],
            'credit_override' => ['nullable', 'boolean'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->approve(
            $order,
            $request->user(),
            $data['audit_notes'] ?? null,
            $request->boolean('credit_override'),
        );

        $order->refresh();

        return $this->redirectAfterAudit(
            $request,
            $order,
            'Order '.$order->number.' approved — status is now '.$order->statusLabel().'.',
            'approved',
        );
    }

    public function requestAdvance(Request $request, Order $order)
    {
        $this->authorize('requestAdvance', $order);

        $data = $request->validate([
            'advance_amount' => ['required', 'numeric', 'min:1'],
            'audit_notes' => ['nullable', 'string', 'max:2000'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->requestAdvance(
            $order,
            $request->user(),
            (float) $data['advance_amount'],
            $data['audit_notes'] ?? null,
        );

        $order->refresh();

        return $this->redirectAfterAudit(
            $request,
            $order,
            'Advance of ৳ '.number_format((float) $data['advance_amount'], 2).' requested for '.$order->number.'. Collect payment, then approve.',
            'advance_requested',
        );
    }

    public function collectAdvance(Request $request, Order $order)
    {
        abort_unless(
            $request->user()->can('orders.approve') && $order->isAwaitingAdvance() && ! $order->isAdvancePaid(),
            403
        );

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank_transfer,cheque,mobile_banking'],
            'reference' => ['nullable', 'string', 'max:120'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->collectAdvance(
            $order,
            $request->user(),
            (float) ($data['amount'] ?? 0),
            $data['method'],
            $data['reference'] ?? null,
        );

        $order->refresh();

        $msg = $order->isAdvancePaid()
            ? 'Advance received for '.$order->number.' — status is now '.$order->statusLabel().'.'
            : 'Partial advance recorded for '.$order->number.'. Remaining balance still due.';

        return $this->redirectAfterAudit(
            $request,
            $order,
            $msg,
            $order->isAdvancePaid() ? 'advance_paid' : 'advance_partial',
        );
    }

    public function reject(Request $request, Order $order)
    {
        $this->authorize('reject', $order);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->reject($order, $request->user(), $data['rejection_reason']);
        $order->refresh();

        return $this->redirectAfterAudit(
            $request,
            $order,
            'Order '.$order->number.' rejected — status is now '.$order->statusLabel().'.',
            'rejected',
        );
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:2000'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ]);

        $wasReserved = $order->stock_reserved || $order->isApproved();
        $this->orders->cancel($order, $request->user(), $data['cancellation_reason']);
        $order->refresh();

        $message = $wasReserved
            ? 'Order '.$order->number.' cancelled and reserved stock released.'
            : 'Order '.$order->number.' cancelled.';

        return $this->redirectAfterAudit($request, $order, $message, 'cancelled');
    }

    public function destroy(Request $request, Order $order)
    {
        $this->authorize('delete', $order);

        $data = $request->validate([
            'return_url' => ['nullable', 'string', 'max:500'],
        ]);

        $number = $order->number;
        $this->orders->deleteBeforeApproval($order, $request->user());

        $return = $data['return_url'] ?? null;
        if (is_string($return) && str_starts_with($return, '/admin/orders')) {
            return redirect()->to($return)
                ->with('success', 'Order '.$number.' deleted before approval.')
                ->with('status_flash', [
                    'order_id' => null,
                    'number' => $number,
                    'status' => 'Deleted',
                    'result' => 'cancelled',
                ]);
        }

        return redirect()->route('orders.index')
            ->with('success', 'Order '.$number.' deleted before approval.');
    }

    /**
     * @return array<string, mixed>
     */
    private function previewPayload(Order $order, ?User $user = null): array
    {
        $order->loadMissing(['shop', 'salesman', 'items.product', 'advanceInvoice']);
        $snapshot = $this->orders->auditSnapshot($order);

        $statusTone = match ($order->status) {
            Order::STATUS_PENDING_AUDIT, Order::STATUS_AWAITING_ADVANCE => 'pending',
            Order::STATUS_APPROVED, Order::STATUS_PICKING, Order::STATUS_PICKED,
            Order::STATUS_PACKED, Order::STATUS_DISPATCHED, Order::STATUS_DELIVERED => 'success',
            Order::STATUS_REJECTED => 'danger',
            Order::STATUS_CANCELLED => 'muted',
            default => 'muted',
        };

        $advancePaid = $order->isAdvancePaid();
        $advanceBalance = $order->advanceInvoice
            ? (float) $order->advanceInvoice->balance
            : (float) ($order->advance_amount ?: 0);

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'status_tone' => $statusTone,
            'source' => ucfirst(str_replace('_', ' ', $order->source)),
            'total' => \App\Support\DemoData::taka($order->total),
            'total_raw' => (float) $order->total,
            'item_count' => $order->item_count,
            'notes' => $order->notes,
            'rejection_reason' => $order->rejection_reason,
            'submitted_at' => $order->submitted_at?->format('d M Y H:i'),
            'shop' => $order->shop?->name,
            'shop_code' => $order->shop?->code,
            'salesman' => $order->salesman?->name,
            'pending_audit' => $order->isPendingAudit() || $order->isAwaitingAdvance(),
            'awaiting_advance' => $order->isAwaitingAdvance(),
            'advance_required' => (bool) $order->advance_required,
            'advance_amount' => $order->advance_amount !== null ? \App\Support\DemoData::taka($order->advance_amount) : null,
            'advance_amount_raw' => (float) ($order->advance_amount ?: 0),
            'advance_paid' => $advancePaid,
            'advance_balance' => \App\Support\DemoData::taka($advanceBalance),
            'advance_balance_raw' => round($advanceBalance, 2),
            'advance_invoice_number' => $order->advanceInvoice?->number,
            'payment_url' => $order->advance_invoice_id
                ? url('/admin/payments?invoice_id='.$order->advance_invoice_id)
                : url('/admin/payments'),
            'collect_advance_url' => $order->isAwaitingAdvance() && ! $advancePaid
                ? route('orders.collect-advance', $order, false)
                : null,
            'can_approve' => $user?->can('approve', $order) ?? false,
            'can_request_advance' => $user?->can('requestAdvance', $order) ?? false,
            'can_reject' => $user?->can('reject', $order) ?? false,
            'can_delete' => $user?->can('delete', $order) ?? false,
            'approve_url' => route('orders.approve', $order, false),
            'request_advance_url' => route('orders.request-advance', $order, false),
            'reject_url' => route('orders.reject', $order, false),
            'delete_url' => route('orders.destroy', $order, false),
            'snapshot' => [
                'credit_ok' => $snapshot['credit_ok'],
                'credit_available' => \App\Support\DemoData::taka($snapshot['credit_available']),
                'stock_ok' => $snapshot['stock_ok'],
            ],
            'items' => $order->items->map(function ($item) {
                $available = $item->product?->availableStock() ?? ($item->available_at_audit ?? 0);
                $ok = $available >= $item->quantity || $item->reserved_quantity > 0;

                return [
                    'sku' => $item->product_sku,
                    'name' => $item->product_name,
                    'qty' => $item->quantity,
                    'available' => (int) $available,
                    'reserved' => (int) $item->reserved_quantity,
                    'unit' => \App\Support\DemoData::taka($item->unit_price),
                    'line' => \App\Support\DemoData::taka($item->line_total),
                    'ok' => $ok,
                ];
            })->values(),
        ];
    }

    private function statusBadgeClass(Order|string $orderOrStatus): string
    {
        if ($orderOrStatus instanceof Order) {
            if ($orderOrStatus->isAwaitingAdvance() && $orderOrStatus->isAdvancePaid()) {
                return 'badge-approved';
            }
            $status = $orderOrStatus->status;
        } else {
            $status = $orderOrStatus;
        }

        return match ($status) {
            Order::STATUS_PENDING_AUDIT, Order::STATUS_AWAITING_ADVANCE => 'badge-pending',
            Order::STATUS_APPROVED => 'badge-approved',
            Order::STATUS_PICKING, Order::STATUS_PICKED => 'badge-processing',
            Order::STATUS_PACKED => 'badge-shipped',
            Order::STATUS_DISPATCHED => 'badge-transit',
            Order::STATUS_DELIVERED => 'badge-delivered',
            Order::STATUS_REJECTED => 'badge-out',
            default => 'badge-hold',
        };
    }

    private function redirectAfterAudit(Request $request, Order $order, string $message, string $result = 'updated')
    {
        $return = $request->input('return_url');
        $flash = [
            'order_id' => $order->id,
            'number' => $order->number,
            'status' => $order->statusLabel(),
            'result' => $result,
        ];

        if (is_string($return) && str_starts_with($return, '/admin/orders')) {
            return redirect()->to($return)
                ->with('success', $message)
                ->with('status_flash', $flash);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', $message)
            ->with('status_flash', $flash);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PartnerInquiry;
use App\Models\User;
use App\Services\PartnerInquiryService;
use Illuminate\Http\Request;
use Throwable;

class PartnerInquiryController extends Controller
{
    public function __construct(private PartnerInquiryService $partners) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('shops.view'), 403);

        $status = $request->get('status');

        $counts = [
            'all' => PartnerInquiry::query()->count(),
            'pending' => PartnerInquiry::query()->whereIn('status', [PartnerInquiry::STATUS_NEW, PartnerInquiry::STATUS_CONTACTED])->count(),
            'accepted' => PartnerInquiry::query()->where('status', PartnerInquiry::STATUS_CONVERTED)->count(),
            'rejected' => PartnerInquiry::query()->where('status', PartnerInquiry::STATUS_CLOSED)->count(),
        ];

        $inquiries = PartnerInquiry::query()
            ->with('shop')
            ->when($status === 'pending', fn ($q) => $q->whereIn('status', [PartnerInquiry::STATUS_NEW, PartnerInquiry::STATUS_CONTACTED]))
            ->when($status === 'accepted', fn ($q) => $q->where('status', PartnerInquiry::STATUS_CONVERTED))
            ->when($status === 'rejected', fn ($q) => $q->where('status', PartnerInquiry::STATUS_CLOSED))
            ->when($status && ! in_array($status, ['pending', 'accepted', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20, ['*'], 'partners_page')
            ->withQueryString();

        $messages = ContactMessage::query()
            ->latest()
            ->paginate(12, ['*'], 'messages_page')
            ->withQueryString();

        return view('admin.partner-inquiries.index', compact('inquiries', 'messages', 'counts'));
    }

    public function accept(Request $request, PartnerInquiry $partnerInquiry)
    {
        abort_unless($request->user()->can('shops.approve'), 403);

        try {
            $result = $this->partners->accept($partnerInquiry, $request->user());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        // Keep temp password in session so admin can Send WhatsApp again on this page load.
        $request->session()->put('partner_cred_'.$result['inquiry']->id, [
            'email' => $result['email'],
            'password' => $result['password'],
            'shop' => $result['shop']->code,
        ]);

        return back()
            ->with('success', 'Accepted. Shop '.$result['shop']->code.' created with portal login '.$result['email'].'.')
            ->with('accepted_inquiry_id', $result['inquiry']->id)
            ->with('whatsapp_chat_url', $result['whatsapp']['chat_url'] ?? null)
            ->with('cred_email', $result['email'])
            ->with('cred_password', $result['password'])
            ->with('cred_shop', $result['shop']->code);
    }

    public function reject(Request $request, PartnerInquiry $partnerInquiry)
    {
        abort_unless($request->user()->can('shops.approve'), 403);

        $this->partners->reject($partnerInquiry, $request->user(), $request->input('admin_notes'));

        return back()->with('success', 'Request marked as rejected.');
    }

    public function pending(Request $request, PartnerInquiry $partnerInquiry)
    {
        abort_unless($request->user()->can('shops.approve'), 403);

        $this->partners->markPending($partnerInquiry, $request->user());

        return back()->with('success', 'Request kept as pending.');
    }

    public function sendWhatsapp(Request $request, PartnerInquiry $partnerInquiry)
    {
        abort_unless($request->user()->can('shops.approve'), 403);

        if ($partnerInquiry->status !== PartnerInquiry::STATUS_CONVERTED) {
            return back()->with('error', 'Accept the request first, then send WhatsApp.');
        }

        $cred = $request->session()->get('partner_cred_'.$partnerInquiry->id);

        if (! $cred || empty($cred['password'])) {
            // Regenerate password and update portal user
            $password = $this->partners->temporaryPassword();
            $email = $partnerInquiry->portal_email ?: $partnerInquiry->email;
            $user = User::query()->where('email', $email)->where('portal', User::PORTAL_SHOP)->first();
            if (! $user) {
                return back()->with('error', 'Portal user not found. Accept the request again.');
            }
            $user->forceFill(['password' => $password])->save();
            $cred = ['email' => $email, 'password' => $password, 'shop' => $partnerInquiry->shop?->code];
            $request->session()->put('partner_cred_'.$partnerInquiry->id, $cred);
        }

        $result = $this->partners->whatsappPayload($partnerInquiry, $cred['password']);

        $flash = ($result['ok'] ?? false) && ($result['via'] ?? '') === 'meta'
            ? 'WhatsApp message sent.'
            : (($result['ok'] ?? false) && ($result['via'] ?? '') === 'log'
                ? 'WhatsApp message logged. Click Open WhatsApp to send manually, or set WHATSAPP_DRIVER=meta.'
                : 'Open WhatsApp to send the login message.');

        if (! empty($result['error']) && empty($result['chat_url'])) {
            return back()->with('error', $result['error']);
        }

        return back()
            ->with('success', $flash)
            ->with('accepted_inquiry_id', $partnerInquiry->id)
            ->with('whatsapp_chat_url', $result['chat_url'] ?? null)
            ->with('cred_email', $cred['email'])
            ->with('cred_password', $cred['password'])
            ->with('cred_shop', $cred['shop'] ?? null);
    }

    public function markMessage(Request $request, ContactMessage $contactMessage)
    {
        abort_unless($request->user()->can('shops.view'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:new,read,closed'],
        ]);

        $contactMessage->update(['status' => $data['status']]);

        return back()->with('success', 'Contact message updated.');
    }
}

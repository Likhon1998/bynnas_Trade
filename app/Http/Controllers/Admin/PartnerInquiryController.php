<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PartnerInquiry;
use Illuminate\Http\Request;

class PartnerInquiryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('shops.view'), 403);

        $inquiries = PartnerInquiry::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20, ['*'], 'partners_page')
            ->withQueryString();

        $messages = ContactMessage::query()
            ->latest()
            ->paginate(15, ['*'], 'messages_page')
            ->withQueryString();

        return view('admin.partner-inquiries.index', compact('inquiries', 'messages'));
    }

    public function update(Request $request, PartnerInquiry $partnerInquiry)
    {
        abort_unless($request->user()->can('shops.approve'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,converted,closed'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $partnerInquiry->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? $partnerInquiry->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Partner inquiry updated.');
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

<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PartnerInquiry;
use App\Rules\BangladeshPhone;
use App\Services\AppNotificationService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home()
    {
        return view('site.home');
    }

    public function about()
    {
        return view('site.about');
    }

    public function contact()
    {
        return view('site.contact');
    }

    public function storeContact(Request $request, AppNotificationService $notifications)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $msg = ContactMessage::query()->create($data + ['status' => ContactMessage::STATUS_NEW]);

        try {
            $notifications->notifyAdmins(
                'New contact message',
                $msg->name.' · '.($msg->subject ?: 'General enquiry'),
                '/admin/partner-leads',
                'contact',
                'info',
            );
        } catch (\Throwable) {
            // non-blocking
        }

        return back()->with('success', 'Thanks — we received your message and will reply soon.');
    }

    public function partner()
    {
        return view('site.partner');
    }

    public function storePartner(Request $request, AppNotificationService $notifications)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:180'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:20', new BangladeshPhone(required: true)],
            'city' => ['nullable', 'string', 'max:80'],
            'business_type' => ['nullable', 'in:retailer,distributor,other'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['phone'] = BangladeshPhone::normalize($data['phone']);

        $inquiry = PartnerInquiry::query()->create($data + ['status' => PartnerInquiry::STATUS_NEW]);

        try {
            $notifications->notifyAdmins(
                'New partner application',
                $inquiry->business_name.' · '.$inquiry->city,
                '/admin/partner-leads',
                'partners',
                'info',
            );
        } catch (\Throwable) {
            // non-blocking
        }

        return redirect()->route('site.partner')->with('success', 'Application received. Our team will review and contact you with next steps.');
    }
}

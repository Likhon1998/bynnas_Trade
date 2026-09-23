<?php

namespace App\Services;

use App\Models\PartnerInquiry;
use App\Models\PriceGroup;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerInquiryService
{
    public function __construct(
        private ShopService $shops,
        private WhatsAppService $whatsapp,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Accept lead → create active shop + portal login (email + temp password).
     *
     * @return array{inquiry: PartnerInquiry, shop: Shop, email: string, password: string, whatsapp: array}
     */
    public function accept(PartnerInquiry $inquiry, User $actor): array
    {
        if ($inquiry->status === PartnerInquiry::STATUS_CONVERTED && $inquiry->shop_id) {
            throw new RuntimeException('This request is already accepted.');
        }

        if (! $inquiry->email) {
            throw new RuntimeException('This request has no email for portal login.');
        }

        if (User::query()->where('email', $inquiry->email)->where('portal', '!=', User::PORTAL_SHOP)->exists()) {
            throw new RuntimeException('That email is already used by a non-shop account.');
        }

        $password = $this->temporaryPassword();

        return DB::transaction(function () use ($inquiry, $actor, $password) {
            $priceGroupId = PriceGroup::query()->where('is_active', true)->orderBy('id')->value('id');

            $shop = $this->shops->create([
                'name' => $inquiry->business_name,
                'owner_name' => $inquiry->contact_name ?: $inquiry->business_name,
                'phone' => $inquiry->phone,
                'email' => $inquiry->email,
                'city' => $inquiry->city,
                'price_group_id' => $priceGroupId,
                'credit_limit' => 0,
                'payment_terms_days' => 21,
                'status' => Shop::STATUS_ACTIVE,
                'notes' => 'Accepted from partner application #'.$inquiry->id,
            ], $actor, [
                'email' => $inquiry->email,
                'password' => $password,
                'name' => $inquiry->contact_name ?: $inquiry->business_name,
                'phone' => $inquiry->phone,
            ]);

            $inquiry->update([
                'status' => PartnerInquiry::STATUS_CONVERTED,
                'shop_id' => $shop->id,
                'portal_email' => $inquiry->email,
                'reviewed_at' => now(),
                'reviewed_by' => $actor->id,
                'admin_notes' => trim(($inquiry->admin_notes ? $inquiry->admin_notes."\n" : '').'Accepted · shop '.$shop->code),
            ]);

            $this->auditLogger->log(
                'partners',
                'accepted',
                "Accepted partner application for {$inquiry->business_name}",
                $inquiry->fresh(),
                null,
                ['shop_id' => $shop->id, 'portal_email' => $inquiry->email],
                $actor,
            );

            $whatsapp = $this->whatsapp->notifyApproval(
                $inquiry->phone,
                $shop->name,
                $inquiry->email,
                $password,
            );

            return [
                'inquiry' => $inquiry->fresh(),
                'shop' => $shop,
                'email' => $inquiry->email,
                'password' => $password,
                'whatsapp' => $whatsapp,
            ];
        });
    }

    public function reject(PartnerInquiry $inquiry, User $actor, ?string $notes = null): PartnerInquiry
    {
        $inquiry->update([
            'status' => PartnerInquiry::STATUS_CLOSED,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
            'admin_notes' => $notes ?: $inquiry->admin_notes,
        ]);

        $this->auditLogger->log('partners', 'rejected', "Rejected partner application for {$inquiry->business_name}", $inquiry, null, null, $actor);

        return $inquiry->fresh();
    }

    public function markPending(PartnerInquiry $inquiry, User $actor): PartnerInquiry
    {
        $inquiry->update([
            'status' => PartnerInquiry::STATUS_NEW,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
        ]);

        return $inquiry->fresh();
    }

    public function whatsappPayload(PartnerInquiry $inquiry, string $password): array
    {
        $email = $inquiry->portal_email ?: $inquiry->email;
        $shopName = $inquiry->shop?->name ?: $inquiry->business_name;

        return $this->whatsapp->notifyApproval(
            $inquiry->phone,
            $shopName,
            $email,
            $password,
        );
    }

    public function temporaryPassword(): string
    {
        // Readable temp password for WhatsApp (no ambiguous chars)
        return 'Bt'.Str::upper(Str::random(4)).rand(10, 99);
    }
}

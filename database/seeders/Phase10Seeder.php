<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\PartnerInquiry;
use Illuminate\Database\Seeder;

class Phase10Seeder extends Seeder
{
    public function run(): void
    {
        PartnerInquiry::query()->firstOrCreate(
            ['email' => 'newshop@example.com', 'business_name' => 'Nova Gadgets'],
            [
                'contact_name' => 'Imran Hossain',
                'phone' => '01711000099',
                'city' => 'Dhaka',
                'business_type' => 'retailer',
                'message' => 'Seeded Phase 10 partner application — interested in audio accessories.',
                'status' => PartnerInquiry::STATUS_NEW,
            ],
        );

        ContactMessage::query()->firstOrCreate(
            ['email' => 'hello.demo@example.com', 'subject' => 'Wholesale catalogue request'],
            [
                'name' => 'Demo Visitor',
                'message' => 'Seeded Phase 10 contact message — please share partnership process.',
                'status' => ContactMessage::STATUS_NEW,
            ],
        );
    }
}

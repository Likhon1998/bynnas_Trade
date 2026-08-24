<?php

namespace Database\Seeders;

use App\Services\AppNotificationService;
use App\Services\RbacService;
use Illuminate\Database\Seeder;

class Phase9Seeder extends Seeder
{
    public function run(): void
    {
        app(RbacService::class)->ensureSystemRoles();
        app(AppNotificationService::class)->seedOperationalAlerts();
    }
}

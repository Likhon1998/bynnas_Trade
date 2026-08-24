<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            Phase2Seeder::class,
            Phase3Seeder::class,
            Phase4Seeder::class,
            Phase5Seeder::class,
            Phase6Seeder::class,
            Phase7Seeder::class,
            Phase8Seeder::class,
            Phase9Seeder::class,
            Phase10Seeder::class,
        ]);
    }
}

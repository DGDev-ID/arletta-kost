<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            KostSeeder::class,
            RoomCategorySeeder::class,
            RoomSeeder::class,
            TenantSeeder::class,
            BillSeeder::class,
            TransactionSeeder::class,
            MidtransConfigSeeder::class,
        ]);
    }
}

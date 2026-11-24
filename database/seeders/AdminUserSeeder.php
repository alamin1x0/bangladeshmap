<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['username' => env('ADMIN_USERNAME', 'admin')],
            ['password' => env('ADMIN_PASSWORD', 'root'), 'status' => 'active'],
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@telcovantage.com'],
            [
                'company'                 => 'telcovantage',
                'role'                    => 'admin',
                'first_name'              => 'TelcoVantage',
                'last_name'               => 'Admin',
                'password'                => Hash::make('admin@123!'),
                'status'                  => 'active',
                'password_reset_required' => false,
            ]
        );

        User::updateOrCreate(
            ['email' => 'marklaurence.tomenio@telcovantage.com'],
            [
                'company'                 => 'telcovantage',
                'role'                    => 'admin',
                'first_name'              => 'Mark Laurence',
                'last_name'               => 'Tomenio',
                'password'                => Hash::make('admin@123!'),
                'status'                  => 'active',
                'password_reset_required' => false,
            ]
        );

        $this->command->info('Admin user created: admin@telcovantage.com / admin@123!');
        $this->command->info('Admin user created: marklaurence.tomenio@telcovantage.com / admin@123!');
    }
}

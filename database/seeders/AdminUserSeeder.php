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
                'first_name'              => 'Telcovantage',
                'last_name'               => 'SuperAdmin',
                'password'                => Hash::make('L@urence_110422'),
                'status'                  => 'active',
                'password_reset_required' => false,
            ]
        );

       

        $this->command->info('Admin user created: admin@telcovantage.com / admin@123!');
        $this->command->info('Admin user created: marklaurence.tomenio@telcovantage.com / admin@123!');
    }
}

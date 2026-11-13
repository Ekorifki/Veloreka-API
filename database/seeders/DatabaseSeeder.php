<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@veloreka.com'],
            [
                'name' => 'Admin Nevermind',
                'role' => 'admin',
                'password' => Hash::make('123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'cody.fisher@veloreka.com'],
            [
                'name' => 'Cody Fisher',
                'role' => 'user',
                'password' => Hash::make('password123'),
            ]
        );

        $this->call([
            ProductSeeder::class,
        ]);
    }
}

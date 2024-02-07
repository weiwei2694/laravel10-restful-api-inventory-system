<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        # user role
        User::create([
            'name' => 'test',
            'email' => 'test@gmail.com',
            'password' => bcrypt('password'),
            'role' => 1
        ]);

        # admin role
        User::create([
            'name' => 'test2',
            'email' => 'test2@gmail.com',
            'password' => bcrypt('password'),
            'role' => 2
        ]);
    }
}

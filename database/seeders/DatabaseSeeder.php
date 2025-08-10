<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'supervisor']);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@masters.clinic',
            'password' => Hash::make('12345678')
        ]);

        $user->assignRole('admin');

        Position::create(['name' => 'الاستقبال']);
    }
}

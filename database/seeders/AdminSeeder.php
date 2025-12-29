<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'username' => 'admin_tekkom',
            'password' => 'tekkom123', // Wajib pakai Hash agar bisa login
            'nama'     => 'Admin Tekkom',
            'email'    => 'admintekkom@example.com',
            'no_hp'    => '081234564857',
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Petugas;
use Illuminate\Support\Facades\Hash;

class PetugasSeeder extends Seeder
{
    public function run(): void
    {

        Petugas::create([
            'username' => 'petugas_tekinfo',
            'password' => 'tekinfo123',
            'nama'     => 'Petugas Tekinfo',
            'email'    => 'petugastekinfo@example.com',
            'no_hp'    => '089876543234',
            'jabatan'  => 'Anggota Tekinfo',
        ]);
    }
}
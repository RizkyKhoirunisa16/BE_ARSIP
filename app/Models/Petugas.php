<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable; // Ditambahkan untuk notifikasi (email OTP)

class Petugas extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id_petugas';
    protected $table = 'petugas';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'username',
        'jabatan',
        'no_hp',
        'otp_code',
        'otp_expires_at'
    ];

    protected $hidden = [
        'password',
    ];

    // Mutator: Meng-hash password secara otomatis saat diatur
    public function setPasswordAttribute($value)
    {
        // Sekarang Hash:: sudah bisa dikenali karena ada 'use' di atas
        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;   
        }
    }
    
    // Relasi ke Surat Masuk
    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class, 'id_petugas', 'id_petugas');
    }

    // Relasi ke Surat Keluar
    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class, 'id_petugas', 'id_petugas');
    }
}
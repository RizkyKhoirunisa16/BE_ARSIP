<?php

namespace App\Models;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable; // Ditambahkan untuk notifikasi (email OTP)

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id_admin';
    protected $table = 'admin'; // Nama tabel tunggal

    protected $fillable = [
        'nama',
        'email',
        'password',
        'username',
        'no_hp',
        'otp_code',
        'otp_expired_at'
    ];

    protected $hidden = [
        'password',
    ];

    // Mutator: Meng-hash password secara otomatis saat diatur
    public function setPasswordAttribute($value)
    {
        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    // Relasi ke Surat Masuk
    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class, 'id_admin', 'id_admin');
    }

    // Relasi ke Surat Keluar
    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class, 'id_admin', 'id_admin');
    }
}
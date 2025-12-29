<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlasifikasiSurat extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_klasifikasi';
    protected $table = 'klasifikasi_surat';

    protected $fillable = [
        'nama_klasifikasi'
    ];

    // Relasi ke Surat Masuk
    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    // Relasi ke Surat Keluar
    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class, 'id_klasifikasi', 'id_klasifikasi');
    }
}
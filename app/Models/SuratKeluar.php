<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_suratkeluar';
    protected $table = 'surat_keluar';

    protected $fillable = [
        'no_surat', 'tgl_surat', 'pengirim', 'perihal', 'file_surat', 
        'klasifikasi_surat', 'tgl_input', 'penerima_surat', 'id_kategori', 'id_petugas', 'id_admin'
    ];

    // Relasi ke tabel master (Foreign Keys)
    public function klasifikasi()
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    public function petugas()
    {
        return $this->belongsTo(Petugas::class, 'id_petugas', 'id_petugas');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        // 1. Ubah nama tabel utama
        Schema::rename('kategori_surat', 'klasifikasi_surat');

        // 2. Ubah kolom Primary Key di tabel tersebut
        Schema::table('klasifikasi_surat', function ($table) {
            $table->renameColumn('id_kategori', 'id_klasifikasi');
            $table->renameColumn('nama_kategori', 'nama_klasifikasi');
        });

        // 3. Ubah Foreign Key di tabel Surat Masuk
        Schema::table('surat_masuk', function ($table) {
            $table->renameColumn('id_kategori', 'id_klasifikasi');
        });

        // 4. Ubah Foreign Key di tabel Surat Keluar
        Schema::table('surat_keluar', function ($table) {
            $table->renameColumn('id_kategori', 'id_klasifikasi');
        });
    }

    public function down(): void
    {
        // Logika untuk mengembalikan jika ada kesalahan (opsional)
        Schema::table('surat_keluar', function ($table) { $table->renameColumn('id_klasifikasi', 'id_kategori'); });
        Schema::table('surat_masuk', function ($table) { $table->renameColumn('id_klasifikasi', 'id_kategori'); });
        Schema::table('klasifikasi_surat', function ($table) {
            $table->renameColumn('id_klasifikasi', 'id_kategori');
            $table->renameColumn('nama_klasifikasi', 'nama_kategori');
        });
        Schema::rename('klasifikasi_surat', 'kategori_surat');
    }

};

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
        // Tambahkan relasi ke tabel surat_masuk
        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori_surat');
            $table->foreign('id_petugas')->references('id_petugas')->on('petugas');
            $table->foreign('id_admin')->references('id_admin')->on('admin');
        });

        // Tambahkan relasi ke tabel surat_keluar
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori_surat');
            $table->foreign('id_petugas')->references('id_petugas')->on('petugas');
            $table->foreign('id_admin')->references('id_admin')->on('admin');
        });
    }

    public function down(): void
    {
        // Hapus relasi dari tabel surat_masuk
        Schema::table('surat_masuk', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->dropForeign(['id_petugas']);
            $table->dropForeign(['id_admin']);
        });

        // Hapus relasi dari tabel surat_keluar
        Schema::table('surat_keluar', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->dropForeign(['id_petugas']);
            $table->dropForeign(['id_admin']);
        });
    }
};

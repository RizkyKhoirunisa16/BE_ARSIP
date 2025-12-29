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
        Schema::create('surat_masuk', function (Blueprint $table) {
            $table->id('id_suratmasuk'); 
            
            // --- Kolom Data ---
            $table->string('no_surat', 100)->unique();
            $table->date('tgl_surat');
            $table->string('pengirim', 150);
            $table->text('perihal');
            $table->string('file_surat', 255)->nullable();
            $table->string('derajat_surat', 50)->nullable();
            $table->dateTime('tgl_input')->useCurrent();
            $table->string('jenis_surat', 100)->nullable();
            $table->string('klasifikasi_surat', 100)->nullable();
            $table->string('tipe_surat', 50)->nullable();

            // --- HANYA DEFINISIKAN KOLOM FK, JANGAN BUAT RELASI DI SINI ---
            $table->unsignedBigInteger('id_kategori')->nullable(); 
            $table->unsignedBigInteger('id_petugas')->nullable();
            $table->unsignedBigInteger('id_admin')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_masuk');
    }
};

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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->dateTime('waktu_transaksi')->nullable();
            $table->bigInteger('saldo_awal_hari_olibs')->nullable();  // Angka besar tanpa desimal
            $table->bigInteger('saldo_akhir_hari_berjalan_olibs')->nullable();  // Angka besar tanpa desimal
            $table->bigInteger('cut_off_olibs')->nullable();  // Angka besar tanpa desimal
            $table->bigInteger('kewajiban_olibs')->nullable();  // Angka besar tanpa desimal
            $table->bigInteger('outgoing_ossw')->nullable();  // Angka besar tanpa desimal, bisa null
            $table->bigInteger('incoming_ossw')->nullable();  // Angka besar tanpa desimal, bisa null
            $table->bigInteger('selisih')->nullable();  // Angka besar tanpa desimal
            $table->string('status')->nullable();  // Status transaksi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemusnahan_kantong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantong_id')->unique()->constrained('kantong_darah')->cascadeOnDelete();
            $table->date('tanggal_pemusnahan');
            $table->text('alasan');
            $table->boolean('sebelum_kedaluwarsa')->default(false);
            $table->text('alasan_khusus')->nullable();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_petugas', 100);
            $table->string('status_sebelumnya', 20);
            $table->timestamps();

            $table->index('tanggal_pemusnahan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemusnahan_kantong');
    }
};
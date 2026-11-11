<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PBI-03: Tabel blood_bags (Kantong Darah) dengan dukungan logika FIFO/FEFO
     */
    public function up(): void
    {
        Schema::create('blood_bags', function (Blueprint $table) {
            $table->id();
            $table->string('bag_code', 50)->unique();
            $table->enum('blood_type', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['+', '-']);
            $table->string('component_type', 100);
            $table->unsignedInteger('volume_ml');
            $table->string('donor_id')->nullable();
            $table->date('collection_date');
            $table->date('expiry_date');
            $table->string('storage_location');
            $table->decimal('storage_temperature', 4, 1)->nullable();
            $table->enum('status', [
                'available',
                'reserved',
                'distributed',
                'expired',
                'discarded',
            ])->default('available');
            $table->timestamps();

            // Indeks untuk optimasi pencarian stok dan algoritma FIFO
            $table->index(['status', 'expiry_date'], 'idx_blood_bags_fifo');
            $table->index(['blood_type', 'rhesus', 'status'], 'idx_blood_bags_match');
            $table->index('collection_date', 'idx_blood_bags_collection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_bags');
    }
};


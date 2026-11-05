<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('screening_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('blood_bag_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('report_number')->unique();   // nomor berita acara
            $t->enum('hiv', ['reaktif', 'non_reaktif']);
            $t->enum('hbsag', ['reaktif', 'non_reaktif']);
            $t->enum('hcv', ['reaktif', 'non_reaktif']);
            $t->enum('sifilis', ['reaktif', 'non_reaktif']);
            $t->date('tested_at');
            $t->string('file_path')->nullable();     // berkas berita acara
            $t->text('notes')->nullable();
            $t->text('correction_reason')->nullable();
            $t->timestamp('corrected_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_results');
    }
};
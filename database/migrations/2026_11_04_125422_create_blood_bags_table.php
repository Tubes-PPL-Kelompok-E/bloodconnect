<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blood_bags', function (Blueprint $t) {
            $t->id();
            $t->string('bag_number')->unique();
            $t->enum('blood_type', ['A', 'B', 'AB', 'O']);
            $t->enum('rhesus', ['+', '-']);
            $t->string('component')->default('WB'); // WB / PRC / TC / FFP
            $t->date('collected_at');
            $t->date('expired_at');
            $t->enum('status', ['karantina', 'ready', 'ditahan'])->default('karantina');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_bags');
    }
};
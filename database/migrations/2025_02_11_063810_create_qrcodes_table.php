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
        Schema::create('qrcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('value')->unique();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->float('radius')->nullable();
            $table->timestamp('waktu_awal')->nullable();
            $table->timestamp('waktu_akhir')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrcodes');
    }
};

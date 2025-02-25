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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users');
            $table->foreignId('opd_id')->constrained('opds');
            $table->foreignId('qrcode_id')->constrained('qrcodes');
            $table->date('date')->nullable();
            $table->time('timestamp')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->enum('status', [
                'present',
                'latest',
                'excused',
                'sick',
                'absent',
            ])->default('absent');
            
            $table->string('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

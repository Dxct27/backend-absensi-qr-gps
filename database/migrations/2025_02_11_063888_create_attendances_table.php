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

            // Ensure opd_id is a string to match opds.id
            $table->string('opd_id');
            $table->foreign('opd_id')->references('id')->on('opds')->onDelete('cascade');

            $table->foreignId('qrcode_id')->constrained('qrcodes')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->time('timestamp')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->enum('status', [
                'hadir',
                'terlambat',
                'izin',
                'sakit',
                'dinas luar',
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

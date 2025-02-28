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
        Schema::table('attendances', function (Blueprint $table) {
            // Modify the 'status' column to include new options
            $table->enum('status', [
                'hadir',
                'terlambat',
                'izin',
                'sakit',
                'dinas luar',
                'absent',
                'lokasi tidak ditemukan', // New: Location not found
                'lokasi di luar radius'   // New: Outside allowed radius
            ])->default('absent')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Rollback to the previous status options
            $table->enum('status', [
                'hadir',
                'terlambat',
                'izin',
                'sakit',
                'dinas luar',
                'absent'
            ])->default('absent')->change();
        });
    }
};

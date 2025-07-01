<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('special_events', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->foreignId('special_event_category_id')->nullable()->after('date')->constrained('special_event_categories')->nullOnDelete();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_events', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->dropForeign(['special_event_category_id']);
            $table->dropColumn('special_event_category_id');
        });
    }
};

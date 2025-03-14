<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('type')->default('daily');
            $table->foreignId('event_id')->nullable()->constrained('special_events')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
        });
    }

};

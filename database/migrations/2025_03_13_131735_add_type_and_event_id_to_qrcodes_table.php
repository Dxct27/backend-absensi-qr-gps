<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('qrcodes', function (Blueprint $table) {
        $table->string('type')->after('opd_id'); // 'daily' or 'special_event'
        $table->foreignId('event_id')->nullable()->after('type')->constrained('special_events')->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('qrcodes', function (Blueprint $table) {
        $table->dropColumn('type');
        $table->dropForeign(['event_id']);
        $table->dropColumn('event_id');
    });
}

};

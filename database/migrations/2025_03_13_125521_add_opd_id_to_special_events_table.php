<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('special_events', function (Blueprint $table) {
            $table->string('opd_id'); // Change to string to match opds.id
            $table->foreign('opd_id')->references('id')->on('opds')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('special_events', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn('opd_id');
        });
    }


};

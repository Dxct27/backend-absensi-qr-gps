<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('qrcodes', function (Blueprint $table) {
            $table->string('url')->nullable()->after('value'); // Add the URL column after `value`
        });
    }

    public function down()
    {
        Schema::table('qrcodes', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};

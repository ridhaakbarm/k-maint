<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('assets', function (Blueprint $table) {
        $table->string('type')->nullable()->after('name');
        $table->string('equip_tag')->nullable()->after('type');
        $table->string('location')->nullable()->after('equip_tag');
    });
}

public function down()
{
    Schema::table('assets', function (Blueprint $table) {
        $table->dropColumn(['type', 'equip_tag', 'location']);
    });
}
};

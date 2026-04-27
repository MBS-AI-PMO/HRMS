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
    Schema::table('general_settings', function (Blueprint $table) {
        $table->integer('late_grace_minutes')->default(0)->after('max_radius');
    });
}

public function down()
{
    Schema::table('general_settings', function (Blueprint $table) {
        $table->dropColumn('late_grace_minutes');
    });
}
};

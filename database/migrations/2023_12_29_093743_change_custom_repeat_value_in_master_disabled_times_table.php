<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCustomRepeatValueInMasterDisabledTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('master_disabled_times', function (Blueprint $table) {
            $table->dropColumn('custom_repeat_value');
        });

        Schema::table('master_disabled_times', function (Blueprint $table) {
            $table->json('custom_repeat_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('master_disabled_times', function (Blueprint $table) {
            $table->string('custom_repeat_value')->nullable();
        });
    }
}

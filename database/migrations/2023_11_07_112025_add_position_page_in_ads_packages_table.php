<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPositionPageInAdsPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('ads_packages', function (Blueprint $table) {
            $table->smallInteger('position_page')->default(1)->comment('На какой странице будет выходить');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('ads_packages', function (Blueprint $table) {
            $table->dropColumn('position_page');
        });
    }
}

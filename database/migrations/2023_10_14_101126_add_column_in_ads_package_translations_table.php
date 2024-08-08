<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInAdsPackageTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('ads_package_translations', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->string('button_text')->nullable();
        });

        Schema::table('shop_ads_packages', function (Blueprint $table) {
            $table->dropForeign('shop_ads_packages_banner_id_foreign');
            $table->dropColumn('banner_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('ads_package_translations', function (Blueprint $table) {
            //
        });
    }
}

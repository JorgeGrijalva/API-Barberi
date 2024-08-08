<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDiscountIdInServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {

            if (Schema::hasColumn('services', 'discount_id')) {
                $table->dropForeign('services_discount_id_foreign');
                $table->dropColumn('discount_id');
            }

        });

        Schema::table('service_masters', function (Blueprint $table) {

            if (Schema::hasColumn('service_masters', 'discount_id')) {
                $table->dropForeign('service_masters_discount_id_foreign');
                $table->dropColumn('discount_id');
                $table->double('discount')->nullable();
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            //
        });
    }
}

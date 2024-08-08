<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDeliveryDateInParcelOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('parcel_orders', function (Blueprint $table) {
            if (Schema::hasColumn('parcel_orders', 'delivery_date')) {
                $table->dropColumn('delivery_date');
            }
            if (Schema::hasColumn('parcel_orders', 'delivery_time')) {
                $table->dropColumn('delivery_time');
            }
        });

        Schema::table('parcel_orders', function (Blueprint $table) {
            $table->dateTime('delivery_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('parcel_orders', function (Blueprint $table) {
            //
        });
    }
}

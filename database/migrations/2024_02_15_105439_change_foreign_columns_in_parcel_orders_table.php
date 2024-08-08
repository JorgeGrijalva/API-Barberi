<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeForeignColumnsInParcelOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
	{
        Schema::table('parcel_orders', function (Blueprint $table) {
			$table->unsignedBigInteger('type_id')->nullable()->change();
			$table->unsignedBigInteger('deliveryman_id')->nullable()->change();
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

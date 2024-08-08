<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnExtraPriceInBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->double('extra_price')->default(0)->nullable();
        });

        Schema::table('booking_extras', function (Blueprint $table) {
            $table->double('price')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('extra_price');
        });

        Schema::table('booking_extras', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
}

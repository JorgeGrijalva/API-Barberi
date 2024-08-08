<?php

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGenderColumnToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->smallInteger('gender')->nullable()->default(Service::ALL_GENDERS);
        });

        Schema::table('service_masters', function (Blueprint $table) {
            $table->smallInteger('gender')->nullable()->default(Service::ALL_GENDERS);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->smallInteger('gender')->nullable()->default(Booking::ALL_GENDERS);
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
            $table->dropColumn('gender');
        });

        Schema::table('service_masters', function (Blueprint $table) {
            $table->dropColumn('gender');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
}

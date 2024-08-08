<?php

use App\Helpers\Utility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('delivery_points', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
            $table->foreignId('region_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->double('price')->default(0);
            $table->jsonb('address');
            $table->string('location');
            $table->integer('fitting_rooms');
            $table->string('img')->nullable();
            $table->double('r_count')->nullable()->default(0);
            $table->double('r_avg')->nullable()->default(0);
            $table->double('r_sum')->nullable()->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_point_working_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_point_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('day', Utility::DAYS);
            $table->string('from', 5)->default('9:00');
            $table->string('to', 5)->default('21:00');
            $table->boolean('disabled')->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_point_closed_dates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_point_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_points');
        Schema::dropIfExists('delivery_point_working_days');
        Schema::dropIfExists('delivery_point_closed_dates');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceMasterPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('service_master_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_master_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('duration')->default('15min');
            $table->string('price_type')->default('fixed'); //from free
            $table->double('price');
            $table->json('smart')->nullable();
            $table->timestamps();
        });

        Schema::create('service_master_price_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_id')->constrained('service_master_prices')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title');
            $table->string('locale')->index();
            $table->unique(['price_id', 'locale']);
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
        Schema::dropIfExists('service_master_price_translations');
        Schema::dropIfExists('service_master_prices');
    }
}

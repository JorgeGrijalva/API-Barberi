<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryPriceTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('delivery_price_translations')) {
            Schema::create('delivery_price_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_price_id')->constrained()->cascadeOnUpdate()->cascadeOnUpdate();
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->string('locale')->index();
                $table->unique(['delivery_price_id', 'locale']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_price_translations');
    }
}

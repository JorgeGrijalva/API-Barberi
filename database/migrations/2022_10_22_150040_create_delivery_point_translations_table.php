<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryPointTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('delivery_point_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_point_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->text('description')->nullable();

            $table->unique(['delivery_point_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_point_translations');
    }
}

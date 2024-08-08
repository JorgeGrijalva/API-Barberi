<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCartTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('gift_cart_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_cart_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title',191);
            $table->text('description')->nullable();
            $table->string('locale')->index();
            $table->unique(['gift_cart_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_cart_translations');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('form_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('required')->default(false);
            $table->json('data');
            $table->timestamps();
        });

        Schema::create('form_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_option_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->unique(['form_option_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('form_option_translations');
        Schema::dropIfExists('form_options');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTermConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('term_conditions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('term_condition_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('term_condition_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('locale')->index();
            $table->unique(['term_condition_id', 'locale']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('term_condition_translations');
        Schema::dropIfExists('term_conditions');
    }
}

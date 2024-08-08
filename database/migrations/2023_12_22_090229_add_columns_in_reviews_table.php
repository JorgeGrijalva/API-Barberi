<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('cleanliness')->nullable();
            $table->boolean('masters')->nullable();
            $table->boolean('location')->nullable();
            $table->boolean('price')->nullable();
            $table->boolean('interior')->nullable();
            $table->boolean('service')->nullable();
            $table->boolean('communication')->nullable();
            $table->boolean('equipment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('cleanliness');
            $table->dropColumn('masters');
            $table->dropColumn('location');
            $table->dropColumn('price');
            $table->dropColumn('interior');
            $table->dropColumn('service');
            $table->dropColumn('communication');
            $table->dropColumn('equipment');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeProductIdInStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropForeign('stories_product_id_foreign');
            $table->dropColumn('product_id');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            //
        });
    }
}

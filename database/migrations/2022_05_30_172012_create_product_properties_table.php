<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('property_groups', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->boolean('active')->default(1);
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::create('property_group_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_group_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('locale')->index();
            $table->string('title', 191);
            $table->unique(['property_group_id', 'locale']);
        });

        Schema::create('property_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_group_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('img')->nullable();
            $table->string('value', 191);
            $table->boolean('active')->default(1);

        });

        Schema::create('product_properties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('property_group_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('property_value_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_properties');
        Schema::dropIfExists('property_values');
        Schema::dropIfExists('property_group_translations');
        Schema::dropIfExists('property_groups');
    }
}

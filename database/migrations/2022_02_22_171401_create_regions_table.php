<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
        });

        Schema::create('region_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->unique(['region_id', 'locale']);
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('active')->default(false);
            $table->string('img')->nullable();
            $table->string('code')->comment('iso2')->nullable();
        });

        Schema::create('country_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->unique(['country_id', 'locale']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::create('city_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->unique(['city_id', 'locale']);
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::create('area_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->unique(['area_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('area_translations');
        Schema::dropIfExists('areas');

        Schema::dropIfExists('city_translations');
        Schema::dropIfExists('cities');

        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('countries');

        Schema::dropIfExists('region_translations');
        Schema::dropIfExists('regions');
    }
}

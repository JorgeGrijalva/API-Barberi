<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('keywords', 191)->nullable();
            $table->bigInteger('parent_id')->default(0)->index();
            $table->tinyInteger('type')->default(1);
            $table->integer('input')->nullable();
            $table->string('img')->nullable();
            $table->boolean('active')->default(1);
            $table->smallInteger('age_limit')->default(0);
            $table->string('status')->default(Category::PENDING);
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
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
        Schema::dropIfExists('categories');
    }
};

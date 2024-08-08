<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();

            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('keywords', 191)->nullable();
            $table->string('img')->nullable();
            $table->string('qr_code')->nullable();
            $table->double('tax')->nullable();
            $table->boolean('active')->default(0);
            $table->enum('status', Product::STATUSES)->default(Product::PENDING);
            $table->integer('min_qty')->default(1);
            $table->integer('max_qty')->default(2147483647);
            $table->boolean('digital')->default(false);
            $table->smallInteger('age_limit')->default(0);
            $table->boolean('visibility')->default(true);
            $table->double('interval')->default(1);
            $table->string('status_note')->nullable();

            $table->double('r_count')->nullable()->default(0);
            $table->double('r_avg')->nullable()->default(0);
            $table->double('r_sum')->nullable()->default(0);
            $table->double('o_count')->nullable()->default(0);
            $table->double('od_count')->nullable()->default(0);
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
        Schema::dropIfExists('products');
    }
}

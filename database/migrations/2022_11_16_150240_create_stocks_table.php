<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('price')->default(0);
            $table->integer('quantity')->default(0);
            $table->dateTime('bonus_expired_at')->nullable();
            $table->dateTime('discount_expired_at')->nullable();
            $table->string('sku')->nullable();
            $table->foreignId('discount_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->double('tax')->nullable();
            $table->string('img')->nullable();
            $table->double('o_count')->nullable()->default(0);
            $table->double('od_count')->nullable()->default(0);
            $table->timestamps();
        });

        Schema::create('stock_extras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('extra_group_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('extra_value_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

        });

        Schema::table('products', function (Blueprint $table) {
            $table->double('min_price')->nullable()->default(0);
            $table->integer('max_price')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
}

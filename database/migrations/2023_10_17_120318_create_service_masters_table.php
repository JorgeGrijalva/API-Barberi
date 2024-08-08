<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('service_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('master_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('commission_fee')->default(0);
            $table->double('price')->default(0);
            $table->boolean('active')->default(false);
            $table->smallInteger('interval')->default(30)->comment('Интервал сеанса в минутах');
            $table->smallInteger('pause')->default(10)->comment('Пауза между сеансами в минутах');
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
        Schema::dropIfExists('service_masters');
    }
}

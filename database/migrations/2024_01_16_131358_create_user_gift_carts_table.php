<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGiftCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('user_gift_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_cart_id')->constrained()->cascadeOnDelete()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnDelete();
            $table->dateTime('expired_at');
            $table->double('price');
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
        Schema::dropIfExists('user_gift_carts');
    }
}

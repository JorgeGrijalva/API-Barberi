<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_master_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('master_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->double('price')->default(0);
            $table->double('rate')->default(1);
            $table->double('discount')->default(0);
            $table->double('commission_fee')->default(0);
            $table->double('service_fee')->default(0);
            $table->string('status')->default(Booking::STATUS_NEW);
            $table->string('canceled_note')->nullable();
            $table->string('note')->nullable();
            $table->string('type')->nullable();
            $table->json('data')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('bookings')->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::dropIfExists('bookings');
    }
}

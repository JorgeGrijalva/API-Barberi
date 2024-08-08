<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('member_ships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('color')->nullable();
            $table->double('price')->nullable();
            $table->string('time')->nullable();
            $table->tinyInteger('sessions')->nullable();
            $table->integer('sessions_count')->nullable();
            $table->timestamps();
        });

        Schema::create('member_ship_services', function (Blueprint $table) {
            $table->foreignId('member_ship_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('member_ship_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_ship_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('term')->nullable();
            $table->string('locale')->index();
            $table->unique(['member_ship_id', 'locale']);
        });

        Schema::create('user_member_ships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_ship_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('color')->nullable();
            $table->double('price')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->tinyInteger('sessions')->nullable();
            $table->integer('sessions_count')->nullable();
            $table->integer('remainder')->nullable();
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
        Schema::dropIfExists('user_member_ships');
        Schema::dropIfExists('member_ship_translations');
        Schema::dropIfExists('member_ship_services');
        Schema::dropIfExists('member_ships');
    }
}

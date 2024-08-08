<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('firstname')->default('firstname');
            $table->string('lastname')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->date('birthday')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->boolean('active')->default(1);
            $table->string('img')->nullable();
            $table->string('password')->nullable();
            $table->string('verify_token')->nullable();
            $table->string('my_referral')->default(Str::random(8));
            $table->string('referral')->nullable();
            $table->longText('firebase_token')->nullable();
            $table->string('location')->nullable();
            $table->double('r_count')->nullable()->default(0);
            $table->double('r_avg')->nullable()->default(0);
            $table->double('r_sum')->nullable()->default(0);
            $table->double('o_count')->nullable()->default(0);
            $table->double('o_sum')->nullable()->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};

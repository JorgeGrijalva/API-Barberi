<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormOptionServiceMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('form_option_service_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_master_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('form_option_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::dropIfExists('form_option_service_masters');
    }
}

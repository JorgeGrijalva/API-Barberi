<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('status')->default(Service::STATUS_NEW);
            $table->string('status_note')->nullable();
            $table->string('img')->nullable();
            $table->double('price')->default(0);
            $table->double('commission_fee')->default(0);
            $table->smallInteger('interval')->default(30)->comment('Интервал сеанса');
            $table->smallInteger('pause')->default(10)->comment('Пауза между сеансами');
            $table->timestamps();
        });

        Schema::create('service_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->unique(['service_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_translations');
        Schema::dropIfExists('services');
    }
}

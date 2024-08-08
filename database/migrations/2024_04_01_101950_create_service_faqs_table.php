<?php

use App\Models\ServiceFaq;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceFaqsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('service_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable();

            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();


            $table->string('type')->default(ServiceFaq::WEB);
            $table->boolean('active')->default(1);
            $table->timestamps();
        });

        Schema::create('service_faq_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_faq_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('locale')->index();
            $table->text('question');
            $table->text('answer')->nullable();

            $table->unique(['service_faq_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_faqs');
        Schema::dropIfExists('service_faq_translations');
    }
}

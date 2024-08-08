<?php

use App\Models\MasterDisabledTime;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterDisabledTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('master_disabled_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('repeats')->default(MasterDisabledTime::DONT_REPEAT);
            $table->string('custom_repeat_type')->default(MasterDisabledTime::DAY);
            $table->string('custom_repeat_value')->default(1);
            $table->date('date');
            $table->string('from', 5)->default('9:00');
            $table->string('to', 5)->default('21:00');
            $table->string('end_type')->default(MasterDisabledTime::NEVER);
            $table->string('end_value')->nullable();
            $table->boolean('can_booking')->default(false);
            $table->timestamps();
        });

        Schema::create('master_disabled_time_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disabled_time_id')->constrained('master_disabled_times')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->text('description');
            $table->unique(['disabled_time_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('master_disabled_time_translations');
        Schema::dropIfExists('master_disabled_times');
    }
}

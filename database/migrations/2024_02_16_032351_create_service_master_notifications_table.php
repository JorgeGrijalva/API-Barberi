<?php

use App\Models\ServiceMasterNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceMasterNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('service_master_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_master_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->smallInteger('notification_time')->default(1);
            $table->string('notification_type')->default(ServiceMasterNotification::WEEK);
            $table->dateTime('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_master_notification_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notify_id')
                ->constrained('service_master_notifications')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->unique(['notify_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_master_notifications');
        Schema::dropIfExists('service_master_notification_translations');
    }
}

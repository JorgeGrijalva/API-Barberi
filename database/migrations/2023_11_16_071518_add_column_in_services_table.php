<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('type')->default(Service::ONLINE);
        });

        Schema::table('service_masters', function (Blueprint $table) {
            $table->string('type')->default(Service::ONLINE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('service_masters', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceMasterIdToFormOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('form_options', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->change();
            $table->foreignId('service_master_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('form_options', function (Blueprint $table) {
            $table->dropForeign('form_options_service_master_id_foreign');
            $table->dropColumn('service_master_id');
        });
    }
}

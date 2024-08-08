<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnInPaymentToPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('payment_to_partners', function (Blueprint $table) {
            $table->dropForeign('payment_to_partners_order_id_foreign');
            $table->dropColumn('order_id');
            $table->morphs('model');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('payment_to_partners', function (Blueprint $table) {
            //
        });
    }
}

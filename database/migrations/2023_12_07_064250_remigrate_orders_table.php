<?php

use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\PaymentToPartner;
use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemigrateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('user_addresses', 'id')) {
                $table->id();
            }
            if (!Schema::hasColumn('user_addresses', 'created_at')) {
                $table->timestamps();
            }
        });

        Schema::dropIfExists('coupon_translations');
        Schema::dropIfExists('order_coupons');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('order_product_refunds');
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('payment_to_partners');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('point_histories');
        Schema::dropIfExists('order_refunds');
        Schema::dropIfExists('order_coupons');
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->id()->from(1000);
            $table->string('type')->default(1)->comment('in_house, seller');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('deliveryman_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('delivery_price_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('delivery_point_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('user_addresses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('status')->default(Order::STATUS_NEW);
            $table->double('total_price', 20)->comment('Сумма с учётом всех налогов и скидок');
            $table->double('commission_fee');
            $table->double('service_fee')->nullable();
            $table->double('delivery_fee')->nullable();
            $table->double('total_discount')->nullable();
            $table->double('total_tax')->default(1);
            $table->float('rate')->default(1);
            $table->string('note', 191)->nullable();
            $table->string('location')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('username')->nullable();
            $table->dateTime('delivery_date')->nullable();
            $table->string('delivery_type')->default(Order::POINT);
            $table->string('img')->nullable();
            $table->string('canceled_note', 255)->nullable();
            $table->string('track_name')->nullable();
            $table->string('track_id')->nullable();
            $table->string('track_url')->nullable();
            $table->boolean('current')->default(false);
            $table->double('coupon_price')->nullable();
            $table->bigInteger('cart_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id()->from(500);
            $table->foreignId('order_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('replace_stock_id')->nullable()->constrained('stocks')->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('replace_quantity')->nullable();
            $table->string('replace_note')->nullable();
            $table->double('origin_price')->default(0);
            $table->double('total_price')->default(0);
            $table->double('tax', 20)->default(0);
            $table->double('discount', 20)->default(0);
            $table->integer('quantity')->default(0);
            $table->boolean('bonus')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->enum('type', ['fix', 'percent'])->default('fix');
            $table->integer('qty')->default(0);
            $table->double('price')->default(0);
            $table->dateTime('expired_at');
            $table->string('img')->nullable();
            $table->string('for')->default('total_price');
            $table->foreignId('shop_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['name', 'shop_id']);
        });

        Schema::create('coupon_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coupon_id')
                ->constrained('coupons')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('locale')->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->unique(['coupon_id', 'locale']);
        });

        Schema::create('order_coupons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id');
            $table->string('name', 191);
            $table->double('price')->nullable();
        });

        Schema::create('order_refunds', function (Blueprint $table) {
            $table->id();
            $table->enum('status', OrderRefund::STATUSES)
                ->default(OrderRefund::STATUS_PENDING)
                ->index();
            $table->text('cause')->nullable();
            $table->text('answer')->nullable();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
        });

        Schema::create('point_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('order_id')->constrained();
            $table->double('price', 20, 2)->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('created_by')->constrained('users');
            $table->bigInteger('user_id')->nullable();
            $table->morphs('model');
            $table->bigInteger('parent_id')->default(0);
            $table->string('type')->default('question');
            $table->string('subject', 191);
            $table->text('content');
            $table->string('status')->default(Ticket::OPEN);
            $table->boolean('read')->default(false);
            $table->timestamps();
        });

        Schema::create('payment_to_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('type')->default(PaymentToPartner::SELLER);
            $table->timestamps();
        });

        if (!Schema::hasColumn('delivery_prices', 'shop_id')) {
            Schema::table('delivery_prices', function (Blueprint $table) {
                $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
}

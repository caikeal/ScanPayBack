<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->string('payment_no')->comment('主键，支付单号')->primary();
            $table->string('third_payment_no')->nullable()->comment('第三方支付流水号')->index();
            $table->string('trade_order_no')->comment('订单编号')->index();
            $table->string('channel')->comment('支付渠道')->index();
            $table->string('transaction_no')->nullable()->comment('渠道流水号')->index();
            $table->decimal('amount_receivable')->default(0)->comment('应收金额');
            $table->decimal('received_amount')->default(0)->comment('实收金额');
            $table->decimal('refunded_amount')->default(0)->comment('已退金额');
            $table->string('failed_reason')->nullable()->comment('失败原因');
            $table->timestamp('finished_at')->nullable()->comment('完成时间');
            $table->string('status')->comment('状态：PROCESSING 支付中，SUCCEEDED 支付成功，FAILED 支付失败，CLOSED 已关闭')->index();
            $table->text('comment')->nullable()->comment('备注');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}

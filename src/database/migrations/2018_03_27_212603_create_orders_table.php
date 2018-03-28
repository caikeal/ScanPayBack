<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->string('order_no')->comment('订单编号')->primary();
            $table->string('customer_str')->nullable()->comment('客户 ID');
            $table->unsignedInteger('created_user_id')->comment('创建者 ID')->index();
            $table->unsignedInteger('company_id')->comment('企业 ID')->index();
            $table->string('title')->comment('订单标题');
            $table->string('receiver_name')->nullable()->comment('收货人姓名');
            $table->string('receiver_mobile')->nullable()->comment('收货人手机号');
            $table->unsignedInteger('receiver_district_id')->nullable()->comment('收货人所属区域 ID');
            $table->string('receiver_province')->nullable()->comment('收货人所属省名称');
            $table->string('receiver_city')->nullable()->comment('收货人所属市名称');
            $table->string('receiver_district')->nullable()->comment('收货人所属县区名称');
            $table->string('receiver_address')->nullable()->comment('收货人详细地址');
            $table->string('receiver_postcode')->nullable()->comment('收货人邮编');
            $table->unsignedInteger('express_company_id')->nullable()->comment('物流公司 ID');
            $table->string('express_company_name')->nullable()->comment('物流公司名称');
            $table->string('express_number')->nullable()->comment('物流编号');
            $table->string('payment_channel')->nullable()->comment('支付渠道，WECHAT_PAY');
            $table->decimal('total_amount')->default(0.00)->comment('总额');
            $table->decimal('reduce_amount')->default(0.00)->comment('优惠总额');
            $table->decimal('amount_receivable')->default(0.00)->comment('应收总额');
            $table->decimal('received_amount')->default(0.00)->comment('实收总额');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamp('send_good_at')->nullable()->comment('发货时间');
            $table->timestamp('received_at')->nullable()->comment('收货时间');
            $table->timestamp('cancelled_at')->nullable()->comment('取消时间');
            $table->string('cancelled_type')->nullable()->comment('订单关闭方式：NOT_PAID 超时未支付关闭，MEMBER 会员手动关闭，MERCHANT 商家手动关闭');
            $table->string('status')->nullable()->comment('订单状态：WAIT_PAY, WAIT_SEND_GOODS, WAIT_CONFIRM_GOODS, COMPLETED, CANCELLED');
            $table->text('comment')->nullable()->comment('备注');
            $table->boolean('is_unpaid_notified')->default(0)->comment('订单未支付是否已提醒');
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
        Schema::dropIfExists('orders');
    }
}

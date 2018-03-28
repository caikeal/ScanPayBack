<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable()->comment('公司名');
            $table->string('status')->default('NORMAL')->comment('公司状态: NORMAL, ABNORMAL');
            $table->string('wx_app_id')->nullable()->comment('微信支付appid');
            $table->string('wx_mch_id')->nullable()->comment('微信支付商户号');
            $table->string('wx_key')->nullable()->comment('微信支付密钥');
            $table->string('wx_cert_path')->nullable()->comment('微信支付证书1');
            $table->string('wx_key_path')->nullable()->comment('微信支付证书2');
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
        Schema::dropIfExists('companies');
    }
}

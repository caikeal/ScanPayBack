<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index()->comment('企业ID');
            $table->string('wx_open_id')->nullable()->comment('wx open id');
            $table->string('name')->nullable()->comment('姓名');
            $table->string('mobile')->comment('手机号');
            $table->string('role')->default('ADMIN')->comment('角色:ADMIN,STAFF');
            $table->text('avatar')->nullable()->comment('头像');
            $table->string('status')->default('NORMAL')->comment('用户状态: NORMAL, ABNORMAL');
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
        Schema::dropIfExists('users');
    }
}

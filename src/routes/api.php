<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ===========================
// 非登录
// ===========================
// 登录
Route::post('/login', 'AuthController@login')->name('login');

// 注册
Route::post('/register', 'AuthController@register')->name('register');

// ===========================
// 登录
// ===========================
Route::group(['middleware' => 'auth.miniprogram'], function () {

    // 订单
    Route::group(['prefix' => 'orders', 'as' => 'orders.'], function () {

        // 扫码下单
        Route::post('scan_order', 'OrderController@scanOrder');
        // 获取订单支付状态
        Route::get('check_pay/{order_no}', 'OrderController@checkPay');

    });
});

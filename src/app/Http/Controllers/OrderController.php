<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2018/3/27
 * Time: 下午8:14
 */

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Overtrue\LaravelWeChat\Facade;

class OrderController extends Controller
{
    public function scanOrder(Request $request)
    {
        // 创建订单
        // 创建支付
        $payment = Facade::payment()->pay([]);
    }
}
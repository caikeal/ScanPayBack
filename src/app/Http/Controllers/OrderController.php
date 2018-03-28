<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2018/3/27
 * Time: 下午8:14
 */

namespace App\Http\Controllers;


use App\Models\Order;
use Illuminate\Http\Request;
use Overtrue\LaravelWeChat\Facade;

class OrderController extends Controller
{
    public function scanOrder(Request $request)
    {
        // 扫取的二维码
        $authCode = $request->input('auth_code');

        // 创建订单
        $orderNo = Order::generateOrderNo();

        $orderParams = [
            "order_no" => $orderNo,
            "created_user_id" => 1,
            "title" => '吃消费',
            "receiver_name" => '用户随机姓名',
            "total_amount" => 0.02,
            "reduce_amount" => 0,
            "amount_receivable" => 0.02,
            "status" => Order::STATUS_WAIT_PAY,
        ];

        $order = Order::create($orderParams);

        // 创建支付

        $payment = Facade::payment()->pay([
            'body' => $order->title,
            'out_trade_no' => $order->order_no,
            'total_fee' => (int) ($order->amount_receivable * 100),
            'auth_code' => $authCode,
        ]);

        return $payment;
    }
}
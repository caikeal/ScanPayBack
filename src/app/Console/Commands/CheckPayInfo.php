<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use EasyWeChat\Factory;
use Illuminate\Console\Command;

class CheckPayInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Payment Check';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $orders = Order::query()->where('status', Order::STATUS_WAIT_PAY)
            ->whereHas('payments', function ($query) {
                $query->where('status', Payment::PROCESSING)->where('channel', Payment::WECHAT_SCAN_PAYMENAT_CHANNEL);
            })->get();

        foreach ($orders as $order) {
            try {
                $company = $order->company()->first();
                $payment = $order->payments()->first();

                if (!$payment) {
                    $order->status = Order::STATUS_CANCELLED;
                    $order->cancelled_at = Carbon::now();
                    $order->cancelled_type = Order::CANCELLED_BY_NOT_PAID;
                    $order->update();
                    continue;
                }

                if (!$company || !$company->wx_app_id) {
                    $order->status = Order::STATUS_CANCELLED;
                    $order->cancelled_at = Carbon::now();
                    $order->cancelled_type = Order::CANCELLED_BY_NOT_PAID;
                    $order->update();
                    $payment->status = Payment::FAILED;
                    $payment->failed_reason = '企业不存在';
                    $payment->update();
                }

                $wechatPay = Factory::payment([
                    // 必要配置
                    'app_id' => $company->wx_app_id,
                    'mch_id' => $company->wx_mch_id,
                    'key' => $company->wx_key, // API 密钥
                    // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
                    'cert_path' => $company->wx_cert_path, // XXX: 绝对路径！！！！
                    'key_path' => $company->wx_key_path, // XXX: 绝对路径！！！！
                    'sandbox' => config('wechat.payment.default.sandbox')
                ]);

                $result = $wechatPay->order->queryByOutTradeNumber($payment->payment_no);
                if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                    // 查询成功！
                    if ($result['trade_state'] == 'SUCCESS') { // 交易成功
                        $payment->received_amount = $result['total_fee'];
                        $payment->finished_at = Carbon::createFromFormat('YmdHis', $result['time_end']);
                        $payment->status = Payment::SUCCEEDED;
                        $payment->update();

                        // 更新交易结果
                        $order->received_amount = accuracy_number($result['total_fee'] / 100, 2);
                        $order->status = Order::STATUS_COMPLETED;
                        $order->payment_channel = Order::PAYMENT_CHANNEL_WECHAT_PAY;
                        $order->paid_at = Carbon::now();
                        $order->update();
                    } elseif ($result['trade_state'] != 'NOTPAY') {
                        // 放弃交易
                        $payment->status = Payment::FAILED;
                        $payment->failed_reason = $result['trade_state'];
                        $payment->update();

                        // 结束订单
                        $order->status = Order::STATUS_CANCELLED;
                        $order->cancelled_at = Carbon::now();
                        $order->cancelled_type = Order::CANCELLED_BY_MEMBER;
                        $order->update();
                    } else {
                        // 创建时间大于 20s 关单
                        if ($order->created_at <= Carbon::now()->subSeconds(20)) {
                            $wechatPay->reverse->byOutTradeNumber($payment->payment_no);
                            // 放弃交易
                            $payment->status = Payment::FAILED;
                            $payment->failed_reason = '放弃交易';
                            $payment->update();

                            // 结束订单
                            $order->status = Order::STATUS_CANCELLED;
                            $order->cancelled_at = Carbon::now();
                            $order->cancelled_type = Order::CANCELLED_BY_MEMBER;
                            $order->update();
                        }
                    }
                } else {
                    payment_log($result);
                }
            } catch (\Exception $e) {
                payment_log([
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace()
                ]);
            }
        }
    }
}

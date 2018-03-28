<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2018/3/27
 * Time: 下午8:14
 */

namespace App\Http\Controllers\Api;


use App\Exceptions\PaymentFailException;
use App\Exceptions\PaymentSettingNotExistException;
use App\Exceptions\PaymentWaitConfirmedException;
use App\Http\Requests\Api\OrderRequest;
use App\Models\Order;
use App\Exceptions\PaymentAuthCodeErrorParseException;
use App\Models\Payment;
use Carbon\Carbon;
use EasyWeChat\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Overtrue\LaravelWeChat\Facade;

class OrderController extends BaseController
{
    /**
     * @param OrderRequest $request
     * @return mixed
     * @author Caikeal <caikeal@qq.com>
     * @throws \Exception
     */
    public function scanOrder(OrderRequest $request)
    {
        // 扫取的二维码
        $authCode = $request->input('auth_code');
        // 获取金额
        $amount = $request->input('amount');
        // 获取登录用户
        $user = $request['user'];
        if (!$user) {
            throw new ModelNotFoundException('用户不存在');
        }
        // 登录企业的配置
        $company = $user->company()->first();
        if (!$company) {
            throw new ModelNotFoundException('未配置企业信息');
        }

        // 创建订单
        $order = $this->createOrder(['user' => $user, 'amount' => $amount]);

        // 创建支付
        $payment = $this->createPayment(['order' => $order, 'authCode' => $authCode, 'company' => $company]);

        return $payment;
    }

    /**
     * 创建订单.
     *
     * @param $params ['user', 'amount']
     * @return mixed
     * @author Caikeal <caikeal@qq.com>
     */
    protected function createOrder($params)
    {
        // 创建订单
        $user = $params['user'];
        $amount = $params['amount'];
        $orderNo = Order::generateOrderNo();
        $orderParams = [
            "order_no" => $orderNo,
            "created_user_id" => $user->id,
            "company_id" => $user->company_id,
            "title" => '吃消费',
            "total_amount" => $amount,
            "reduce_amount" => 0,
            "amount_receivable" => $amount,
            "status" => Order::STATUS_WAIT_PAY,
        ];
        $order = Order::create($orderParams);
        return $order;
    }

    /**
     * 创建支付
     * @param $params ['authCode', 'order', 'company']
     * @return mixed
     * @throws \Exception
     * @author Caikeal <caikeal@qq.com>
     */
    protected function createPayment($params)
    {
        $payType = $this->checkPaymentCategory($params['authCode']);

        switch ($payType) {
            case 'WECHATPAY':
                return $this->handleWechatpay($params);
                break;
            case 'ALIPAY':
                return $this->handleAlipay($params);
                break;
            default:
                throw new PaymentAuthCodeErrorParseException('非支持的付款码');
                break;
        }
    }

    /**
     * 验证支付类型
     * @param $authCode
     * @return string
     * @author Caikeal <caikeal@qq.com>
     */
    protected function checkPaymentCategory($authCode)
    {
        // 微信是10、11、12、13、14、15开头的数字
        if (preg_match('/^1[0-5][0-9]+$/', $authCode)) {
            return 'WECHATPAY';
        } else
        // 支付宝是25~30开头的数字
        if (preg_match('/^(2[5-9]|30)[0-9]+$/', $authCode)) {
            return 'ALIPAY';
        } else {
            throw new PaymentAuthCodeErrorParseException('非支持的付款码');
        }
    }

    /**
     * 微信条码支付
     * @param $params
     * @return mixed
     * @author Caikeal <caikeal@qq.com>
     * @throws \Exception
     */
    protected function handleWechatpay($params)
    {
        if (!$params['company']->wx_app_id) {
            throw new PaymentSettingNotExistException('支付设置不存在');
        }

        $wechatPay = Factory::payment([
            // 必要配置
            'app_id'             => $params['company']->wx_app_id,
            'mch_id'             => $params['company']->wx_mch_id,
            'key'                => $params['company']->wx_key, // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $params['company']->wx_cert_path, // XXX: 绝对路径！！！！
            'key_path'           => $params['company']->wx_key_path, // XXX: 绝对路径！！！！
            'sandbox'            => config('wechat.payment.default.sandbox')
        ]);

        $paymentNo = Payment::generateOrderNo(); // 微信支付单号
        $wxPaymentInfo = $wechatPay->pay([
            'body' => $params['order']->title,
            'out_trade_no' => $paymentNo,
            'total_fee' => (int) ($params['order']->amount_receivable * 100),
            'auth_code' => $params['authCode'],
        ]);

        // 微信支付通信失败
        if ($wxPaymentInfo['return_code'] == 'FAIL') {
            payment_log($wxPaymentInfo);

            // 创建支付信息
            Payment::create([
                'payment_no' => $paymentNo,
                'trade_order_no' => $params['order']->order_no,
                'channel' => Payment::WECHAT_PAYMENAT_CHANNEL,
                'amount_receivable' => $params['order']->amount_receivable * 100,
                'failed_reason' => $wxPaymentInfo['return_msg'],
                'status' => Payment::FAILED,
                'comment' => json_encode($wxPaymentInfo)
            ]);
            // 更新订单
            $params['order']->status = Order::STATUS_CANCELLED;
            $params['order']->cancelled_type = Order::CANCELLED_BY_NOT_PAID;
            $params['order']->cancelled_at = Carbon::now();
            $params['order']->update();

            throw new PaymentFailException('微信支付系统繁忙，请稍后再试：'.$wxPaymentInfo['return_msg']);
        }

        // 处理支付失败的情况
        if ($wxPaymentInfo['return_code'] == 'FAIL') {
            if ($wxPaymentInfo['err_code'] == 'SYSTEMERROR'
                || $wxPaymentInfo['err_code'] == 'BANKERROR'
                ||$wxPaymentInfo['err_code'] == 'USERPAYING') {

                // 创建支付信息
                Payment::create([
                    'payment_no' => $paymentNo,
                    'trade_order_no' => $params['order']->order_no,
                    'channel' => Payment::WECHAT_PAYMENAT_CHANNEL,
                    'amount_receivable' => $params['order']->amount_receivable * 100,
                    'failed_reason' => $wxPaymentInfo['err_code'],
                    'status' => Payment::PROCESSING,
                    'comment' => json_encode($wxPaymentInfo)
                ]);

                // 需要等待查询结果
                throw new PaymentWaitConfirmedException('支付等待确认');
            } else {
                payment_log($wxPaymentInfo);

                // 创建支付信息
                Payment::create([
                    'payment_no' => $paymentNo,
                    'trade_order_no' => $params['order']->order_no,
                    'channel' => Payment::WECHAT_PAYMENAT_CHANNEL,
                    'amount_receivable' => $params['order']->amount_receivable * 100,
                    'failed_reason' => $wxPaymentInfo['err_code'],
                    'status' => Payment::FAILED,
                    'comment' => json_encode($wxPaymentInfo)
                ]);
                // 更新订单
                $params['order']->status = Order::STATUS_CANCELLED;
                $params['order']->cancelled_type = Order::CANCELLED_BY_NOT_PAID;
                $params['order']->cancelled_at = Carbon::now();
                $params['order']->update();

                // 支付失败
                throw new PaymentFailException('微信支付失败，错误码：'.$wxPaymentInfo['err_code'].', 错误描述：'.$wxPaymentInfo['err_code_des']);
            }
        }

        // 支付成功
        $payment = Payment::create([
            'payment_no' => $paymentNo,
            'trade_order_no' => $params['order']->order_no,
            'channel' => Payment::WECHAT_PAYMENAT_CHANNEL,
            'transaction_no' => $wxPaymentInfo['transaction_id'],
            'amount_receivable' => $params['order']->amount_receivable * 100,
            'received_amount' => $wxPaymentInfo['total_fee'],
            'finished_at' => Carbon::createFromFormat('YmdHis', $wxPaymentInfo['time_end']),
            'status' => Payment::SUCCEEDED
        ]);
        // 更新订单
        $params['order']->status = Order::STATUS_COMPLETED;
        $params['order']->received_amount = $payment->received_amount;
        $params['order']->payment_channel = Order::PAYMENT_CHANNEL_WECHAT_PAY;
        $params['order']->paid_at = Carbon::now();
        $params['order']->update();

        return $payment;
    }

    protected function handleAlipay($params)
    {

    }
}
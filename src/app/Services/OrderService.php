<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2018/3/28
 * Time: 下午4:37
 */

namespace App\Services;


use App\Exceptions\PaymentAuthCodeErrorParseException;
use App\Exceptions\PaymentFailException;
use App\Exceptions\PaymentSettingNotExistException;
use App\Exceptions\PaymentWaitConfirmedException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use EasyWeChat\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderService extends BaseService
{
    /**
     * 创建订单.
     *
     * @param $params ['user', 'amount']
     * @return mixed
     * @author Caikeal <caikeal@qq.com>
     */
    public function createOrder($params)
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
    public function createPayment($params)
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
            throw new PaymentSettingNotExistException('微信支付设置不存在');
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
        $params['order']->received_amount = accuracy_number($payment->received_amount / 100, 2);
        $params['order']->payment_channel = Order::PAYMENT_CHANNEL_WECHAT_PAY;
        $params['order']->paid_at = Carbon::now();
        $params['order']->update();

        return $params['order'];
    }

    protected function handleAlipay($params)
    {
        // todo alipay 支付
        throw new PaymentSettingNotExistException('支付宝支付设置不存在');
    }

    /**
     * 查看订单支付状态
     * @param $orderNo
     * @param User $user
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author Caikeal <caikeal@qq.com>
     */
    public function checkPayment($orderNo, User $user)
    {
        $order = Order::where('order_no', $orderNo)->first();
        if (!$order || $order->company_id != $user->company_id) {
            throw new ModelNotFoundException('订单不存在');
        }
        // 非待支付，直接返回结果
        if ($order->status != Order::STATUS_WAIT_PAY) {
            return $order;
        }

        $payment = $order->payments()->first();
        if (!$payment) {
            throw new ModelNotFoundException('支付失败');
        }

        $company = $user->company()->first();
        if (!$company) {
            throw new ModelNotFoundException('企业不存在');
        }

        switch ($payment->channel) {
            case Payment::WECHAT_PAYMENAT_CHANNEL:
                $order = $this->checkWxPayment($order, $payment, $company);
                break;
            case Payment::ALI_PAYMENAT_CHANNEL:
                $order = $this->checkAliPayment($order, $payment, $company);
                break;
            default:
                break;
        }

        return $order;
    }

    /**
     * 检查微信支付
     * @param $order
     * @param $payment
     * @param $company
     * @return mixed
     * @author Caikeal <caikeal@qq.com>
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    protected function checkWxPayment($order, $payment, $company)
    {
        if (!$company->wx_app_id) {
            throw new PaymentSettingNotExistException('支付宝支付设置不存在');
        }

        $wechatPay = Factory::payment([
            // 必要配置
            'app_id'             => $company->wx_app_id,
            'mch_id'             => $company->wx_mch_id,
            'key'                => $company->wx_key, // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $company->wx_cert_path, // XXX: 绝对路径！！！！
            'key_path'           => $company->wx_key_path, // XXX: 绝对路径！！！！
            'sandbox'            => config('wechat.payment.default.sandbox')
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
            }
        }

        return $order;
    }

    protected function checkAliPayment($order, $payment, $company)
    {
        // todo alipay 支付检查
        throw new PaymentSettingNotExistException('支付宝支付设置不存在');
    }
}
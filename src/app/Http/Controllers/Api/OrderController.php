<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2018/3/27
 * Time: 下午8:14
 */

namespace App\Http\Controllers\Api;


use App\Http\Requests\Api\OrderRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    /**
     * @var OrderService
     */
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

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
        $order = $this->orderService->createOrder(['user' => $user, 'amount' => $amount]);

        // 创建支付
        $order = $this->orderService->createPayment(['order' => $order, 'authCode' => $authCode, 'company' => $company]);

        return $order;
    }

    /**
     * 检查订单支付状态
     * @param $orderNo
     * @param Request $request
     * @return mixed
     * @author Caikeal <caikeal@qq.com>
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function checkPay($orderNo, Request $request)
    {
        // 获取登录用户
        $user = $request['user'];
        if (!$user) {
            throw new ModelNotFoundException('用户不存在');
        }
        $order = $this->orderService->checkPayment($orderNo, $user);

        return $order;
    }
}
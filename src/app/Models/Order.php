<?php

namespace App\Models;

use App\Library\Util\Math;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Order extends Model
{
    const STATUS_WAIT_PAY           = "WAIT_PAY";//等待客户付款
    const STATUS_WAIT_SEND_GOODS    = "WAIT_SEND_GOODS";//等待分销商发货，即：客户已付款
    const STATUS_WAIT_CONFIRM_GOODS = "WAIT_CONFIRM_GOODS";//等待客户确认收货，即：分销商已发货
    const STATUS_COMPLETED          = "COMPLETED";//客户已签收/订单已结束
    const STATUS_CANCELLED          = "CANCELLED";//订单关闭/取消

    const CANCELLED_BY_NOT_PAID = 'NOT_PAID';           // 超时未支付关闭
    const CANCELLED_BY_MEMBER   = 'MEMBER';             // 会员手动关闭
    const CANCELLED_BY_MERCHANT = 'MERCHANT';           // 商家手动关闭

    const PAYMENT_CHANNEL_WECHAT_PAY = 'WECHAT_PAY';
    const PAYMENT_CHANNEL_ALIPAY = 'ALIPAY';

    const STATUS_MAP = [
        self::STATUS_WAIT_PAY           => '待支付',
        self::STATUS_WAIT_SEND_GOODS    => '已支付，待发货',
        self::STATUS_WAIT_CONFIRM_GOODS => '已发货，待收货',
        self::STATUS_COMPLETED          => '已完成',
        self::STATUS_CANCELLED          => '取消',
    ];

    protected $primaryKey   = 'order_no';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $dates = [
        'created_at',
        'updated_at',
        'paid_at',
        'received_at',
        'send_good_at',
        'cancelled_at',
    ];

    protected $casts = [
        'is_unpaid_notified' => 'bool'
    ];

    protected $fillable = [
        "order_no",
        "customer_str",
        "created_user_id",
        "company_id",
        "title",
        "receiver_name",
        "receiver_mobile",
        "receiver_district_id",
        "receiver_province",
        "receiver_city",
        "receiver_district",
        "receiver_address",
        "receiver_postcode",
        "express_company_id",
        "express_company_name",
        "express_number",
        "payment_channel",
        "total_amount",
        "reduce_amount",
        "amount_receivable",
        "received_amount",
        "paid_at",
        "received_at",
        "send_good_at",
        "cancelled_at",
        "cancelled_type",
        "status",
        "comment",
        "is_unpaid_notified",
        "created_at",
        "updated_at",
    ];

    /**
     * 生成订单号.
     *
     * @return string
     *
     * @author         JohnWang <takato@vip.qq.com>
     */
    public static function generateOrderNo()
    {
        $sn = Math::generateSn('10');

        /* 到数据库里查找是否已存在 */
        try {
            self::findOrFail($sn);
        } catch (ModelNotFoundException $e) {
            return $sn;
        }

        /* 如果有重复的，则重新生成 */

        return self::generateOrderNo();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'trade_order_no', 'order_no');
    }
}

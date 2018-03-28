<?php

namespace App\Models;

use App\Library\Util\Math;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Payment extends Model
{
    const WECHAT_SCAN_PAYMENAT_CHANNEL = 'WECHAT_SCAN_PAY';
    const ALI_SCAN_PAYMENAT_CHANNEL = 'ALI_SCAN_PAY';

    const PROCESSING = 'PROCESSING';
    const SUCCEEDED = 'SUCCEEDED';
    const FAILED = 'FAILED';
    const CLOSED = 'CLOSED';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'payment_no';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $dates = [
        'created_at',
        'updated_at',
        'finished_at',
    ];

    protected $casts = [

    ];

    protected $fillable = [
        "payment_no"        ,
        "third_payment_no"  ,
        "trade_order_no"    ,
        "channel"           ,
        "transaction_no"    ,
        "amount_receivable" ,
        "received_amount"   ,
        "refunded_amount"   ,
        "failed_reason"     ,
        "finished_at"       ,
        "status"            ,
        "comment"           ,
        "credential"        ,
        "extra"             ,
        "created_at"        ,
        "updated_at"        ,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'trade_order_no', 'order_no');
    }

    /**
     * 生成支付单号.
     *
     * @return string
     *
     * @author         JohnWang <takato@vip.qq.com>
     */
    public static function generateOrderNo()
    {
        $sn = Math::generateSn('11');

        /* 到数据库里查找是否已存在 */
        try {
            self::findOrFail($sn);
        } catch (ModelNotFoundException $e) {
            return $sn;
        }

        /* 如果有重复的，则重新生成 */
        return self::generateOrderNo();
    }
}

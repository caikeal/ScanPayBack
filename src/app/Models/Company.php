<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    const STATUS_NORMAL = 'NORMAL';
    const STATUS_ABNORMAL = 'ABNORMAL';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'status',
        'wx_app_id',
        'wx_mch_id',
        'wx_key',
        'wx_cert_path',
        'wx_key_path',
    ];
}

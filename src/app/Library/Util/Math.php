<?php

/*
 * This file is part of the car/chedianai_bc.
 *
 * (c) chedianai <i@chedianai.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Library\Util;

class Math
{
    /**
     * 生成订单号.
     *
     * @param string $prefix
     *
     * @return string
     */
    public static function generateSn($prefix = '')
    {
        /* 选择一个随机的方案 */
        mt_srand((float) microtime() * 1000000);
        $ymd = date('Ymd', time());

        return $prefix.$ymd.str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}

<?php

/*
 * This file is part of the car/chedianai_bc.
 *
 * (c) chedianai <i@chedianai.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (!function_exists('is_debug_mode')) {
    /**
     * 判断当前这个应用是否处于调试状态
     *
     * @return string
     */
    function is_debug_mode()
    {
        return app('config')->get('app.debug');
    }
}

if (!function_exists('shadowMobile')) {
    /**
     * 遮挡手机号规则.
     *
     * @param $mobile
     *
     * @return string
     */
    function shadowMobile($mobile)
    {
        $len = strlen($mobile);
        // 判断位数  <=3 && >=1 遮挡后1位
        if ($len <= 3 && $len >= 1) {
            $mobile = substr_replace($mobile, '*', -1);
        } elseif // <=6 && > 3 遮挡最后3位
        ($len <= 6 && $len > 3) {
            $mobile = substr_replace($mobile, '***', -3);
        }
        // >6 前3位和后4位不遮挡
        elseif ($len > 6) {
            $mobile = substr_replace($mobile, '***', 3, $len - 7);
        }

        return $mobile;
    }
}

if (!function_exists('keytolower')) {
    /**
     * 将数组键小写化[递归].
     *
     * @param array $arr
     *
     * @return array
     *
     * @author Caikeal <caikeal@qq.com>
     */
    function keytolower(array $arr)
    {
        $lower = [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $middle = keytolower($v);
            } else {
                $middle = $v;
            }
            $lower[strtolower($k)] = $middle;
        }

        return $lower;
    }
}

if (!function_exists('distanceBetween')) {
    /**
     * 2点间距离.
     *
     * @param $start
     * @param $end
     *
     * @return float|int
     *
     * @author Caikeal <caikeal@qq.com>
     */
    function distanceBetween($start, $end)
    {
        $lngStart = (pi() / 180) * $start[0];
        $lngEnd   = (pi() / 180) * $end[0];

        $latStart = (pi() / 180) * $start[1];
        $latEnd   = (pi() / 180) * $end[1];

        // 地球半径
        $R        = 6371;
        $distance = acos(sin($latStart) * sin($latEnd) +
                cos($latStart) * cos($latEnd) * cos($lngStart - $lngEnd)) * $R; // km

        return $distance * 1000;
    }
}

if (!function_exists('accuracy_number')) {
    /**
     * 处理数字小数点位数.
     *
     * @param $value
     * @param int    $accuracy
     * @param string $rule
     *
     * @return string
     *
     * @author Caikeal <caikeal@qq.com>
     */
    function accuracy_number($value, int $accuracy, string $rule='round'): string
    {
        if (!in_array($rule, ['round', 'ceil', 'floor'])) {
            throw new InvalidArgumentException('暂未设定该方法');
        }

        if (!$value) { // 处理是null的为0
            $value = 0;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException("${value} 不是数字无法处理");
        }

        $value = $rule($value, $accuracy); // 四舍五入
        return number_format($value, $accuracy, '.', '');
    }
}

if (!function_exists('multi_collect')) {
    /**
     * array 转 collect.
     *
     * @param $array
     *
     * @return \Illuminate\Support\Collection
     *
     * @author Caikeal <caikeal@qq.com>
     */
    function multi_collect($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = multi_collect($v);
            }
        }

        return collect($array);
    }
}

if (!function_exists('payment_log')) {
    /**
     * 支付日志记录
     *
     * @param $array
     *
     * @author Caikeal <caikeal@qq.com>
     *
     * @throws Exception
     */
    function payment_log($array)
    {
        $logger = new \Monolog\Logger('payment');

        $logger->pushHandler(
            new \Monolog\Handler\StreamHandler(storage_path('logs/'.$logger->getName().'.log'))
        );

        $logger->debug('payment', $array);
    }
}


<?php

/*
 * This file is part of the car/chedianai_bc.
 *
 * (c) chedianai <i@chedianai.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    \App\Exceptions\ParamInvalidException::class                         => 101001,      // 传入参数异常
    \App\Exceptions\MiniProgramInvalidUsageException::class              => 101002,      // 小程序请求错误
    \App\Exceptions\MiniProgramDecryptErrorException::class              => 101003,      // 小程序解码错误
    \App\Exceptions\SmsCodeErrorException::class                         => 101004,      // 验证码错误
    \App\Exceptions\SmsSendFailException::class                          => 101005,      // 发送失败
    \App\Exceptions\SmsSendOverLimitException::class                     => 101006,      // 发送频率太高
    \App\Exceptions\PaymentAuthCodeErrorParseException::class            => 101007,      // 支付码错误
    \App\Exceptions\PaymentSettingNotExistException::class               => 101008,      // 支付设置不存在
    \App\Exceptions\PaymentFailException::class                          => 101009,      // 支付失败
    \App\Exceptions\PaymentWaitConfirmedException::class                 => 101010,      // 支付等待确认

    /*
     * =================================================================================================
     * 小程序 API 错误码
     * =================================================================================================
     */

    \App\Exceptions\MiniProgram\ErrorParsedTokenException::class         =>  202001,    // token转义失败
    \App\Exceptions\MiniProgram\TokenBlacklistedException::class         =>  202002,    // token进入黑名单
    \App\Exceptions\MiniProgram\TokenExpiredException::class             =>  202003,    // token已失效
];

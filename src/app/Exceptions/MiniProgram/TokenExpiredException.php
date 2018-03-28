<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2018/1/30
 * Time: 上午9:22
 */

namespace App\Exceptions\MiniProgram;


use Illuminate\Auth\AuthenticationException;

class TokenExpiredException extends AuthenticationException
{

}
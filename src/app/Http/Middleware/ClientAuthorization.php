<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2018/1/29
 * Time: 下午10:22
 */

namespace App\Http\Middleware\MiniProgram;

use App\Exceptions\MiniProgram\ErrorParsedTokenException;
use App\Models\User;
use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Exceptions\MiniProgram\TokenBlacklistedException as SystemTokenInvalidException;
use App\Exceptions\MiniProgram\TokenExpiredException as SystemTokenExpiredException;

class ClientAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws ErrorParsedTokenException
     * @throws SystemTokenExpiredException
     * @throws SystemTokenInvalidException
     */
    public function handle($request, Closure $next)
    {
        if (config('app.debug') && config('custom.user.user_id')) {
            $request['user'] = User::find(config('custom.user.user_id'));
            $response = $next($request);
            return $response;
        }

        // common use
        try {
            JWTAuth::parseToken();
            $token = JWTAuth::getToken();
        } catch (JWTException $e) {
            throw new ErrorParsedTokenException('未知编码的 token');
        }

        // Try to verify token
        try {
            // If sucessful, save user on request

            $request['user'] = JWTAuth::authenticate($token);
        }

        catch (TokenBlacklistedException $e) {
            throw new SystemTokenInvalidException('token 已经失效');
        }

            // If token has expired...
        catch (TokenExpiredException $e) {

//            try {
//                // Try to refresh token
//                $token = JWTAuth::refresh($token);
//                JWTAuth::setToken($token);
//                $refreshToken = $token;
//
//                // Authenticate with new token, save user on request
//                $request['user'] = JWTAuth::authenticate($token);
//            }
//
//                // If token refresh period has expired...
//            catch(TokenExpiredException | TokenBlacklistedException $e) {
//
//                // Return 401 status
                throw new SystemTokenExpiredException('token 已经过期');
//            }
        }

        $response = $next($request);

        // If need refresh token will return token in header
//        if (isset($refreshToken)) {
//            $response->header('Authorization', 'Bearer '.$refreshToken);
//        }

        return $response;
    }
}
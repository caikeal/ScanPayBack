<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \App\Exceptions\ParamInvalidException::class,
        \App\Exceptions\HttpPayloadException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        if (!$request->expectsJson()) {
            return parent::render($request, $e);
        }

        $payload = [];

        switch (true) {
            case $e instanceof ModelNotFoundException:
                $payload['message'] = $e->getMessage() ?: 'Resource Not Found';
                $status             = Response::HTTP_NOT_FOUND;
                break;
            case $e instanceof MethodNotAllowedHttpException:
            case $e instanceof NotFoundHttpException:
                $payload['message'] = $e->getMessage() ?: 'Endpoint Not Found';
                $status             = Response::HTTP_NOT_FOUND;
                break;
            case $e instanceof AuthenticationException:
                $payload['message'] = $e->getMessage();
                $status             = Response::HTTP_UNAUTHORIZED;
                break;
            case $e instanceof AuthorizationException:
            case $e instanceof AccessDeniedHttpException:
                $payload['message'] = $e->getMessage();
                $status             = Response::HTTP_FORBIDDEN;
                break;
            case $e instanceof TokenMismatchException:
                $payload['message'] = $e->getMessage();
                $status             = 419;
                break;
            case $e instanceof ParamInvalidException:
            case $e instanceof ValidationException:
                $status  = $e->status;
                $payload = $this->changeValidationExceptionToResponse($e);
                break;
            case $e instanceof HttpPayloadException:
                $status  = Response::HTTP_BAD_REQUEST;
                $payload = $this->badRequest($e);
                break;
            default:
                $payload['message'] = $e->getMessage() ?: 'Internal Server Error';
                $status             = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
        }

        $payload = $this->debugInfo($payload, $e); // 处理 debug 信息

        return $this->toJsonResponse($payload, $status);
    }

    /**
     * Bad Request response.
     *
     * @param HttpPayloadException $e
     *
     * @return array $payload
     *
     * @author         JohnWang <takato@vip.qq.com>
     */
    protected function badRequest(HttpPayloadException $e)
    {
        $payload['meta']    = $e->getPayload();
        $payload['message'] = $e->getMessage();

        if (array_key_exists(get_class($e), config('error_code'))) {
            $payload['code'] = config('error_code'.'.'.get_class($e));
        } else {
            $payload['code'] = 0;
        }

        return $payload;
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param ValidationException | ParamInvalidException $e
     *
     * @return array $payload
     */
    protected function changeValidationExceptionToResponse($e)
    {
        $errors     = $e->errors();
        $firstError = array_first($errors);
        $payload    = [
            'message' => $firstError[0],
            'errors'  => $errors,
        ];

        return $payload;
    }

    /**
     * @param $payload
     * @param $status
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @author         JohnWang <takato@vip.qq.com>
     */
    protected function toJsonResponse($payload, $status)
    {
        return response()->json($payload, $status);
    }

    /**
     * @param $payload
     * @param Exception $e
     *
     * @return array
     *
     * @author Caikeal <caikeal@qq.com>
     */
    public function debugInfo($payload, Exception $e)
    {
        if (config('app.debug')) {
            $payload = array_merge($payload, [
                'debug' => [
                    'message' => $e->getMessage(),
                    'class'   => get_class($e),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => explode("\n", $e->getTraceAsString()),
                ],
            ]);
        }

        return $payload;
    }
}

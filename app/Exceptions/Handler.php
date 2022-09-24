<?php

namespace App\Exceptions;

use ErrorException;
use Facade\FlareClient\Http\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Token;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e){

        });

        $this->renderable(function (Throwable $e) {
            return $this->handleException($e);
        });
    }

    public function handleException( Throwable $e){

        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();
            $defaultMessage = \Symfony\Component\HttpFoundation\Response::$statusTexts[$code];
            $message = $e->getMessage() == "" ? $defaultMessage : $e->getMessage();
            return $this->errorResponse($message, $code);
        } else if ($e instanceof ModelNotFoundException) {
            $model = strtolower(class_basename($e->getModel()));
            return $this->errorResponse("Does not exist any instance of {$model} with the given id", HttpResponse::HTTP_NOT_FOUND);
        } else if ($e instanceof AuthorizationException) {
            return $this->errorResponse($e->getMessage(), HttpResponse::HTTP_FORBIDDEN);
        }else if ($e instanceof QueryException) {
            return $this->errorResponse(["message" => $e->getMessage(), "message_user" => "Error Sql"], HttpResponse::HTTP_FORBIDDEN);
        } else if ($e instanceof Token) {
            return $this->errorResponse($e->getMessage(), HttpResponse::HTTP_UNAUTHORIZED);
        } else if ($e instanceof AuthenticationException) {
            return $this->errorResponse($e->getMessage(), HttpResponse::HTTP_UNAUTHORIZED);
        } else if ($e instanceof ValidationException) {
            $errors = $e->validator->errors()->getMessages();
            return $this->errorResponse($errors, HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } else {
            if (config('app.debug'))
                return $this->dataResponse($e->getMessage());
            else {
                return $this->errorResponse('Try later', HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }




    /*
        FUNCIONES DE AYUDA
    */

        /**
         * Data Response
         * @param $data
         * @return JsonResponse
         */
        public function dataResponse($data): JsonResponse
        {
            return response()->json(['content' => $data], HttpResponse::HTTP_OK);
        }

        /**
         * Success Response
         * @param string $message
         * @param int $code
         * @return JsonResponse
         */
        public function successResponse(string $message, $code = HttpResponse::HTTP_OK): JsonResponse
        {
            return response()->json(['success' => $message, 'code' => $code], $code);
        }

        /**
         * Error Response
         * @param $message
         * @param int $code
         * @return JsonResponse
         *
         */
        public function errorResponse($message, $code = HttpResponse::HTTP_BAD_REQUEST): JsonResponse
        {
            return response()->json(['error' => $message, 'code' => $code], $code);
        }
}

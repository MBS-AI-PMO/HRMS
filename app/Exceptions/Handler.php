<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\Mailer\Exception\TransportException;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
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
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable  $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable  $exception)
    {
        if ($exception instanceof TransportException) {
            \Illuminate\Support\Facades\Log::error('SMTP TransportException', [
                'message' => $exception->getMessage(),
                'previous' => optional($exception->getPrevious())->getMessage(),
                'url' => optional($request)->fullUrl(),
            ]);

            // Surface the real SMTP reason — the old generic text hid auth/SSL/timeout failures.
            $detail = trim((string) $exception->getMessage());
            $message = $detail !== ''
                ? 'Mail configuration error: '.$detail
                : 'Mail configuration error. Please check your mail settings.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return redirect()->back()->withErrors(['email' => $message])->withInput();
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Unauthenticated. Please log in to continue.'),
            ], 401);
        }

        return redirect()->guest(route('login'));
    }
}

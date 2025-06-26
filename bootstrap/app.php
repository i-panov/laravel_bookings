<?php

use App\Exceptions\AppException;
use App\Http\Middleware\AuthenticateWithApiToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AuthenticateWithApiToken::class);
    })
    ->withProviders([
        \Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, $request) {
            if (!($exception instanceof Throwable)) {
                \Log::error([
                    'message' => 'Неожиданный тип исключения',
                    'type' => gettype($exception),
                    'class' => is_object($exception) ? get_class($exception) : null,
                    'exception' => $exception,
                ]);

                return response()->json(['error' => 'Неизвестная ошибка'], 500);
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'error' => 'Требуется аутентификация',
                ], 403);
            }

            if ($exception instanceof AccessDeniedHttpException) {
                return response()->json([
                    'error' => $exception->getMessage() ?: 'Доступ запрещён',
                ], 403);
            }

            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => 'Ресурс не найден',
                ], 404);
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'error' => 'Ошибка валидации',
                    'messages' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof AppException) {
                $msg = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();
                \Log::error('Произошла ошибка приложения: ' . $msg);

                return response()->json([
                    'error' => $exception->getMessage(),
                ], $exception->getCode() ?: 500);
            }

            \Log::error('Произошла ошибка: ' . $exception->getMessage());

            return response()->json([
                'error' => 'Произошла ошибка на сервере',
            ], 500);
        });
    })->create();

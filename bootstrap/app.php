<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => null);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        //   Handle Model Not Found Exception
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            $modelName = class_basename($e->getModel()); // class name (App\Models\Post)

            return errorResponse(
                404,
                "{$modelName} not found!"
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {

            return errorResponse(
                404,
                "Not found!"
            );
        });

        $exceptions->render(function (AuthenticationException $e, $request) {

            return errorResponse(
                401,
                "Unauthenticated!"
            );
        });

        //   Handle Internal Server Errors
        $exceptions->render(function (Throwable $e, $request) {

            Log::error('Server Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return errorResponse(
                500,
                'Internal Server Error'
            );
        });
    })

    ->create();

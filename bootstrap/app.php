<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // error responses keep the same JSON shape as your trait.
        $makeResponder = function () {
            return new class
            {
                use \App\Traits\ApiResponse;
            };
        };

        // Validation errors -> 422
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) use ($makeResponder) {
            $responder = $makeResponder();

            return $responder->errorResponse('Invalid fields', 422, $e->errors());
        });

        // Authorization / forbidden -> 403
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) use ($makeResponder) {
            $responder = $makeResponder();

            return $responder->errorResponse($e->getMessage() ?: 'Forbidden', 403);
        });

        // Symfony access denied -> 403
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) use ($makeResponder) {
            $responder = $makeResponder();

            return $responder->errorResponse($e->getMessage() ?: 'Forbidden', 403);
        });

        // Not found -> 404
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) use ($makeResponder) {
            $responder = $makeResponder();

            return $responder->errorResponse($e->getMessage() ?: 'Not Found', 404);
        });

        // Generic HttpException (handle 402 Payment Required)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) use ($makeResponder) {
            $responder = $makeResponder();

            if ($e->getStatusCode() === 402) {
                return $responder->errorResponse($e->getMessage() ?: 'Payment Required', 402);
            }

            // Not handled here, let other handlers continue
            return null;
        });

        // Fallback for unexpected errors -> 500 (avoid exposing details in production)
        $exceptions->render(function (\Throwable $e, Request $request) use ($makeResponder) {

            // If app is in debug mode let the default handler show details
            if (app()->has('config') && config('app.debug')) {
                return null;
            }

            $responder = $makeResponder();

            return $responder->errorResponse('Server Error', 500);
        });

        $exceptions->render(function (DomainException $e, Request $request) use ($makeResponder) {
            $responder = $makeResponder();

            return $responder->errorResponse($e->getMessage(), 400); // 400 Bad Request untuk error bisnis
        });
    })->create();

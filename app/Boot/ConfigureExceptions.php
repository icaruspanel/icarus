<?php
declare(strict_types=1);

namespace App\Boot;

use App\Http\Exceptions\NotAuthenticated;
use App\Http\Exceptions\OutOfOperatingContext;
use App\Http\Responses\ApiResponse;
use Closure;
use Icarus\Domain\AuthToken\Exceptions\AuthenticationFailed;
use Icarus\Domain\AuthToken\Exceptions\InvalidCredentials;
use Icarus\Domain\AuthToken\Exceptions\UnableToAuthenticate;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConfigureExceptions
{
    public static function make(): Closure
    {
        return new self()(...);
    }

    public function __invoke(Exceptions $exceptions): void
    {
        $exceptions->render(function (NotAuthenticated $e, Request $request) {
            return ApiResponse::error([
                'message' => 'You are not authenticated',
            ], code: Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (OutOfOperatingContext $e, Request $request) {
            return ApiResponse::error([
                'message' => $e->getMessage(),
            ], code: Response::HTTP_BAD_REQUEST);
        });

        $exceptions->render(function (AuthenticationFailed $e, Request $request) {
            if ($e instanceof InvalidCredentials) {
                return ApiResponse::error([
                    'message' => 'Invalid credentials',
                ], code: Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof UnableToAuthenticate) {
                return ApiResponse::error([
                    'message' => 'Unable to authenticate',
                    'details' => $e->getMessage(),
                ], code: Response::HTTP_UNAUTHORIZED);
            }

            return ApiResponse::error([
                'message' => 'Unable to authenticate',
            ], code: Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }

}

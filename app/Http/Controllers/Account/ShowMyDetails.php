<?php
declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\UserResponse;
use Icarus\Kernel\Auth\AuthenticatedUser;
use Illuminate\Http\JsonResponse;

final class ShowMyDetails
{
    public function __invoke(AuthenticatedUser $user): JsonResponse
    {
        return ApiResponse::item(UserResponse::make($user));
    }
}

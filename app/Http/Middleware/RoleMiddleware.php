<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\ResponseError;
use App\Models\User;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role): mixed
    {
        $roles = is_array($role) ? $role : explode('|', (string)$role);

        /** @var User $user */
        $user = auth('sanctum')->user();

        if ($user->hasAnyRole($roles) || $user->hasRole('admin')) {
            return $next($request);
        }

        return $this->errorResponse(
            'ERROR_101',
            __('errors.' . ResponseError::ERROR_101, locale: request('lang', 'en')),
            Response::HTTP_FORBIDDEN
        );
    }
}

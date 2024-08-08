<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\ResponseError;
use App\Traits\ApiResponse;
use Closure;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanctumCheck
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (\Illuminate\Http\Response|RedirectResponse) $next
     * @return mixed
     * @throws Exception
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (auth('sanctum')->check()) {
            return $next($request);
        }

        return $this->errorResponse(
            ResponseError::ERROR_100,
            __('errors.' . ResponseError::ERROR_100, locale: request('lang', 'en')),
            Response::HTTP_UNAUTHORIZED
        );
    }
}

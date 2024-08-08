<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\ResponseError;
use App\Models\User;
use App\Traits\ApiResponse;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class CheckSellerShop
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return JsonResponse
     * @throws Exception
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        if (!auth('sanctum')->check()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_100]);
        }

        /** @var User $user */
        $user = auth('sanctum')->user();

        if ($user?->shop && $user?->hasRole(['seller', 'admin'])) {
            return $next($request);
        }

        if ($user?->moderatorShop && $user?->role == 'moderator' || $user?->role == 'deliveryman') {
            return $next($request);
        }

        if ($user?->shop && $user?->role == 'admin') {
            return $next($request);
        }

        return $this->onErrorResponse(['code' => ResponseError::ERROR_204, 'http' => 401]);
    }
}

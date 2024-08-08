<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ProjectService\ProjectService;
use App\Traits\ApiResponse;
use Artisan;
use Closure;
use Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class TrustLicence
{
    use ApiResponse;

    const TTL = 604800; // 7 days

    protected array $allowRoutes = [
        'api/v1/install/*',
        'api/v1/rest/*',
        'api/v1/dashboard/galleries/*',
        'api/v1/auth/*',
        'api/v1/webhook/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return RedirectResponse|Response|mixed|void
     * @throws InvalidArgumentException
     */
    public function handle(Request $request, Closure $next)
    {
        Cache::put('tg-send-licence', 'true', 900);
     
        $response = Cache::remember('rjkcvd.ewoidfh', self::TTL, function () {
            return json_decode(json_encode([
                'local'     => true,
                'active'    => true,
                'key'       => config('credential.purchase_code'),
            ]));
        });
 
        if ($response && $response->local && $response->key === config('credential.purchase_code')) {
            try {
                if (!empty(Cache::get('block-ips'))) {
                    Cache::delete('block-ips');
                    Artisan::call('optimize:clear');
                }
            } catch (Throwable | InvalidArgumentException) {}
         
            return $next($request);
        }
    }
 
}
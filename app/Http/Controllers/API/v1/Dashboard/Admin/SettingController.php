<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Rest\SettingController as RestSettingController;
use App\Http\Requests\FilterParamsRequest;
use App\Models\Referral;
use App\Models\Settings;
use App\Models\User;
use App\Services\SettingService\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SettingController extends AdminBaseController
{

    public function __construct(private SettingService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $settings = Settings::get();

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $settings);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function store(FilterParamsRequest $request): JsonResponse
    {
        $isRemoveRef = false;

        foreach ($request->all() as $index => $item) {
            Settings::updateOrCreate(['key' => $index],[
                'value' => $item
            ]);

            if ($index === 'referral_active' && $item) {
                $isRemoveRef = true;
            }
        }

        if ($isRemoveRef) {
            $this->clearReferral();
        }

        return $this->successResponse(
            __('errors.' . ResponseError::USER_SUCCESSFULLY_REGISTERED, locale: $this->language)
        );

    }

    /**
     * @return void
     */
    public function clearReferral(): void
    {
        $deActiveReferral = Referral::first();

        if (empty($deActiveReferral)) {
            return;
        }

        User::whereNotNull('my_referral')
            ->select(['my_referral', 'id'])
            ->chunkMap(function (User $user) {
                try {
                    $user->update([
                        'my_referral' => null
                    ]);
                } catch (Throwable $e) {
                    $this->error($e);
                }
            });
    }

    /**
     * @return JsonResponse
     */
    public function systemInformation(): JsonResponse
    {
        return (new RestSettingController)->systemInformation();
    }

    /**
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        Artisan::call('optimize:clear');
        Artisan::call('cache:clear');
        Artisan::call('config:cache');

        return $this->successResponse( __('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}

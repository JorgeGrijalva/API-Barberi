<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller\Report\Booking;

use App\Http\Controllers\API\v1\Dashboard\Seller\SellerBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Report\Booking\PaymentRequest;
use App\Http\Resources\BookingMasterReportResource;
use App\Repositories\ReportRepository\BookingRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class PaymentTypeController extends SellerBaseController
{
    public function __construct(
        private BookingRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * @param PaymentRequest $request
     * @return Collection
     */
    public function payments(PaymentRequest $request): Collection
    {
        return $this->repository->payments($request->merge(['shop_id' => $this->shop->id])->all());
    }

    /**
     * @param PaymentRequest $request
     * @return Collection
     */
    public function summary(PaymentRequest $request): Collection
    {
        return $this->repository->summary($request->merge(['shop_id' => $this->shop->id])->all());
    }

    /**
     * @param PaymentRequest $request
     * @return array
     */
    public function cards(PaymentRequest $request): array
    {
        return $this->repository->cards($request->merge(['shop_id' => $this->shop->id])->all());
    }

    /**
     * @param PaymentRequest $request
     * @return Collection
     */
    public function chart(PaymentRequest $request): Collection
    {
        return $this->repository->chart($request->merge(['shop_id' => $this->shop->id])->all());
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function masters(FilterParamsRequest $request): AnonymousResourceCollection
    {
        return BookingMasterReportResource::collection(
            $this->repository->masters($request->merge(['shop_id' => $this->shop->id])->all())
        );
    }

    /**
     * @param PaymentRequest $request
     * @return array[]
     */
    public function statistic(PaymentRequest $request): array
    {
        return $this->repository->statistic($request->merge(['shop_id' => $this->shop->id])->all());
    }
}

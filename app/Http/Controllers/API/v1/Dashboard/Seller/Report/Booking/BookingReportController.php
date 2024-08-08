<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller\Report\Booking;

use App\Http\Controllers\API\v1\Dashboard\Seller\SellerBaseController;
use App\Http\Requests\Report\Booking\BookingRequest;
use App\Repositories\ReportRepository\BookingRepository;

class BookingReportController extends SellerBaseController
{
    public function __construct(private BookingRepository $repository)
    {
        parent::__construct();
    }

    /**
     * @param BookingRequest $request
     * @return array
     */
    public function statistic(BookingRequest $request): array
    {
        return $this->repository->statistic($request->merge(['shop_id' => $this->shop->id])->all());
    }
}

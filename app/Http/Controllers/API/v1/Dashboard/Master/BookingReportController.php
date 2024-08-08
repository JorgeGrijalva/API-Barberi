<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Master;

use App\Http\Requests\Report\Booking\BookingRequest;
use App\Repositories\ReportRepository\BookingRepository;

class BookingReportController extends MasterBaseController
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
        return $this->repository->statistic($request->merge(['master_id' => auth('sanctum')->id()])->all());
    }
}

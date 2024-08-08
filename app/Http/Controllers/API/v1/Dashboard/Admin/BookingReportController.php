<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Http\Requests\Report\Booking\BookingRequest;
use App\Repositories\ReportRepository\BookingRepository;

class BookingReportController extends AdminBaseController
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
        return $this->repository->statistic($request->all());
    }

}

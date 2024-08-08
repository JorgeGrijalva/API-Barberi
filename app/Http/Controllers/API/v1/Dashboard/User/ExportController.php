<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Repositories\OrderRepository\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExportController extends UserBaseController
{

    public function __construct(private OrderRepository $repository)
    {
        parent::__construct();
    }

    /**
     * @param int $id
     * @return Response|JsonResponse
     */
    public function orderExportPDF(int $id): Response|JsonResponse
    {
        $result = $this->repository->exportPDF($id);

        if (is_array($result)) {
            return $this->onErrorResponse($result);
        }

        return $result;
    }

    /**
     * @param int $id
     * @return Response|JsonResponse
     */
    public function exportByParentPDF(int $id): Response|JsonResponse
    {
        $result = $this->repository->exportByParentPDF($id);

        if (is_array($result)) {
            return $this->onErrorResponse($result);
        }

        return $result;
    }

}

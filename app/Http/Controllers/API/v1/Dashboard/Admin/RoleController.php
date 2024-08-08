<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends AdminBaseController
{
    use ApiResponse;
    /**
     * Handle the incoming request.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function __invoke(FilterParamsRequest $request): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            Role::all()
        );
    }
}

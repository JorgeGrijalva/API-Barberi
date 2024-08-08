<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\FormOptionResource;
use App\Models\FormOption;
use App\Repositories\FormOptionRepository\FormOptionRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FormOptionController extends Controller
{
    use ApiResponse;

    public function __construct(private FormOptionRepository $repository)
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
        $formOptions = $this->repository->paginate($request->all());

        return FormOptionResource::collection($formOptions);
    }

    /**
     * Display the specified resource.
     *
     * @param FormOption $formOption
     * @return JsonResponse
     */
    public function show(FormOption $formOption): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            FormOptionResource::make($this->repository->show($formOption))
        );
    }
}

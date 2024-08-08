<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\TagResource;
use App\Repositories\TagRepository\TagRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends RestBaseController
{

    public function __construct(
        private TagRepository $repository,
    )
    {
        parent::__construct();
    }
    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $stories = $this->repository->paginate($request->all());

        return TagResource::collection($stories);
    }

}

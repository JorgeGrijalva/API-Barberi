<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Requests\FilterParamsRequest;
use App\Repositories\StoryRepository\StoryRepository;

class StoryController extends RestBaseController
{

    public function __construct(
        private StoryRepository $repository,
    )
    {
        parent::__construct();
    }
    /**
     * @param FilterParamsRequest $request
     * @return array
     */
    public function paginate(FilterParamsRequest $request): array
    {
        return $this->repository->list($request->all());
    }

}

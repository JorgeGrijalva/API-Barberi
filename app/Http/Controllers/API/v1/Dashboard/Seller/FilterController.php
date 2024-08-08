<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Http\Requests\FilterRequest;
use App\Http\Requests\SearchRequest;
use App\Repositories\FilterRepository\FilterRepository;

class FilterController extends SellerBaseController
{

    public function __construct(
        private FilterRepository $repository,
    )
    {
        parent::__construct();
    }

    public function filter(FilterRequest $request): array
    {
        $validated = $request->validated();
        $validated['shop_ids'][] = $this->shop->id;

        return $this->repository->filter($validated);
    }

    public function search(SearchRequest $request): array
    {
        return $this->repository->search($request->validated());
    }
}

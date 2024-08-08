<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\TransactionResource;
use App\Repositories\TransactionRepository\TransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends UserBaseController
{

    public function __construct(private TransactionRepository $transactionRepository)
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $transactions = $this->transactionRepository->paginate(
            $request->merge(['user_id' => auth('sanctum')->id()])->all()
        );

        return TransactionResource::collection($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = $this->transactionRepository->show($id);

        if (empty($transaction) || $transaction->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            TransactionResource::make($transaction)
        );
    }
}

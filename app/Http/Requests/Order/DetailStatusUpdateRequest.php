<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class DetailStatusUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status'        => [
                'string',
                'required',
                Rule::in(Order::STATUSES)
            ],
            'canceled_note' => [
                'string',
                'max:255',
                'required_if:status,' . Order::STATUS_CANCELED
            ],
        ];
    }
}

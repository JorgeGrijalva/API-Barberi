<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;

class AddReviewRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'rating'        => 'required|numeric',
            'comment'       => 'string',
            'images'        => 'array',
            'images.*'      => 'string',
            'cleanliness'   => 'bool',
            'masters'       => 'bool',
            'location'      => 'bool',
            'price'         => 'bool',
            'interior'      => 'bool',
            'service'       => 'bool',
            'communication' => 'bool',
            'equipment'     => 'bool',
        ];
    }
}

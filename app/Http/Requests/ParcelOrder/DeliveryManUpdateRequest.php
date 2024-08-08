<?php
declare(strict_types=1);

namespace App\Http\Requests\ParcelOrder;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DeliveryManUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'deliveryman_id' => [
                'int',
                'required',
                Rule::exists('users', 'id')
            ],
        ];
    }
}

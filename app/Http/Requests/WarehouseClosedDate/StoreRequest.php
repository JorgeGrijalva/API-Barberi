<?php
declare(strict_types=1);

namespace App\Http\Requests\WarehouseClosedDate;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
            ],
            'dates'     => 'array',
            'dates.*'   => 'date_format:Y-m-d',
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Requests\MasterClosedDate;

use App\Http\Requests\BaseRequest;

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
            'dates'     => 'array',
            'dates.*'   => 'date_format:Y-m-d',
        ];
    }
}

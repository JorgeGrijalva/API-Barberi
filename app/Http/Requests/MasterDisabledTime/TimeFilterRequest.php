<?php
declare(strict_types=1);

namespace App\Http\Requests\MasterDisabledTime;

use App\Http\Requests\BaseRequest;

class TimeFilterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'service_master_id' => 'required|int|exists:service_masters,id',
            'perPage'           => 'int|max:101',
            'start_date'        => 'date_format:Y-m-d H:i',
            'end_date'          => 'date_format:Y-m-d H:i',
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Requests\MasterDisabledTime;

use App\Helpers\Utility;
use App\Http\Requests\BaseRequest;
use App\Models\MasterDisabledTime;
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
            'repeats'               => Rule::in(MasterDisabledTime::REPEATS),
            'custom_repeat_type'    => Rule::in(MasterDisabledTime::CUSTOM_REPEAT_TYPE),
            'custom_repeat_value'   => 'array',
            'custom_repeat_value.0' => 'integer|min:1',
            'custom_repeat_value.1' => ['string', Rule::in(Utility::DAYS)],
            'custom_repeat_value.2' => ['string', Rule::in(Utility::DAYS)],
            'custom_repeat_value.3' => ['string', Rule::in(Utility::DAYS)],
            'custom_repeat_value.4' => ['string', Rule::in(Utility::DAYS)],
            'custom_repeat_value.5' => ['string', Rule::in(Utility::DAYS)],
            'custom_repeat_value.6' => ['string', Rule::in(Utility::DAYS)],
            'custom_repeat_value.7' => ['string', Rule::in(Utility::DAYS)],
            'date'                  => 'required|string|min:10|max:10|date_format:Y-m-d',
            'from'                  => 'required|string|min:5|max:5|date_format:H:i',
            'to'                    => 'required|string|min:5|max:5|date_format:H:i',
            'end_type'              => Rule::in(MasterDisabledTime::END_TYPES),
            'end_value'             => 'string',
            'title'                 => 'array',
            'title.*'               => 'string|max:191',
            'description'           => 'array',
            'description.*'         => 'string',
            'can_booking'           => 'bool',
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Requests\FormOption;

use App\Http\Requests\BaseRequest;
use App\Models\FormOption;
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
        $rules = FormOption::MULTIPLE_CHOICE .','. FormOption::SINGLE_ANSWER.','. FormOption::DROP_DOWN;

        return [
            'service_master_id'    => 'exists:service_masters,id',
            'shop_id'              => 'exists:shops,id',
            'required'             => 'required|boolean',
            'active'               => 'boolean',
            'title'                => 'required|array',
            'title.*'              => 'required|string|min:1|max:191',
            'description'          => 'array',
            'description.*'        => 'string|min:1',
            'data'                 => 'required|array',
            'data.*.answer_type'   => 'required|'.Rule::in(FormOption::ANSWER_TYPES),
            'data.*.question'      => 'required|string',
            'data.*.answer'        => 'required_if:data.*.answer_type,'. $rules .'|array',
            'data.*.required'      => 'required|boolean',
        ];
    }
}

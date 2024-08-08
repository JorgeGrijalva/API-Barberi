<?php
declare(strict_types=1);

namespace App\Http\Requests\PropertyValue;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'property_group_id' => [
                'integer',
                Rule::exists('property_groups', 'id')
            ],
            'value'          => 'required|string|max:191',
            'active'         => 'boolean',
            'images'         => 'array',
            'images.0'       => 'string',
        ];
    }
}


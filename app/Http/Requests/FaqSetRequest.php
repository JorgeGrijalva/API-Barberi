<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class FaqSetRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'active'        => ['bool', Rule::in([0, 1])],
            'question'      => 'array',
            'question.*'    => 'string',
            'answer'        => 'array',
            'answer.*'      => 'string',
        ];
    }
}

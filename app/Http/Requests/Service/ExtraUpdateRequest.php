<?php
declare(strict_types=1);

namespace App\Http\Requests\Service;

use App\Http\Requests\BaseRequest;

class ExtraUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'extras'            => 'required|array',
            'extras.*'          => 'required|array',
            'extras.*.active'   => 'required|boolean',
            'extras.*.price'    => 'required|numeric',
            'extras.*.title'    => 'required|array',
            'extras.*.title.*'  => 'required|string|min:1|max:191',
            'extras.*.img'      => 'required|string|min:1|max:191',
        ];
    }
}


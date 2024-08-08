<?php
declare(strict_types=1);

namespace App\Http\Requests\Gallery;

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
            'images'    => 'required|array',
            'images.*'  => 'string',
        ];
    }
}

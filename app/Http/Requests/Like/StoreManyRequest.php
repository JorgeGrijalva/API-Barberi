<?php
declare(strict_types=1);

namespace App\Http\Requests\Like;

use App\Http\Requests\BaseRequest;
use App\Models\Like;
use Illuminate\Validation\Rule;

class StoreManyRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'types'             => 'required|array',
            'types.*.type'      => ['required', Rule::in(array_keys(Like::TYPES))],
            'types.*.type_id'   => 'required|integer',
        ];
    }
}

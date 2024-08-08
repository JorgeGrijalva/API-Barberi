<?php
declare(strict_types=1);

namespace App\Http\Requests\PropertyGroup;

use App\Http\Requests\BaseRequest;
use App\Models\Category;
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
            'title'         => 'array',
            'title.*'       => 'required|string|min:2|max:191',
            'type'          => 'required|string',
            'active'        => 'boolean',
        ];
    }
}


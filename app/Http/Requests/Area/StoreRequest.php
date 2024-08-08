<?php
declare(strict_types=1);

namespace App\Http\Requests\Area;

use App\Http\Requests\BaseRequest;
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
            'active'        => 'required|boolean',
            'city_id'       => ['required', 'integer', Rule::exists('cities', 'id')],
            'title'         => 'required|array',
            'title.*'       => 'required|string|max:191',
        ];
    }
}

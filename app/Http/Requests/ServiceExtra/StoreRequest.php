<?php
declare(strict_types=1);

namespace App\Http\Requests\ServiceExtra;

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
            'service_id'    => ['required', 'int', Rule::exists('services', 'id')],
            'active'        => 'boolean',
            'price'         => 'numeric',
            'title'         => 'required|array',
            'title.*'       => 'required|string|min:1|max:191',
            'description'   => 'array',
            'description.*' => 'string|min:1',
            'img'           => 'string',
        ];
    }
}

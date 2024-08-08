<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'properties'   => 'required|array',
            'properties.*' => [
                'required',
                Rule::exists('property_values', 'id')->where('active', true)
            ],
        ];
    }
}

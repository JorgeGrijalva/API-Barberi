<?php
declare(strict_types=1);

namespace App\Http\Requests\DigitalFile;

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
            'active'     => 'required|boolean',
            'file'       => 'required|mimes:zip',
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('digital', true)
                    
            ],
        ];
    }
}

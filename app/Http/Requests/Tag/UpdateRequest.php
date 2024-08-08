<?php
declare(strict_types=1);

namespace App\Http\Requests\Tag;

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
            'product_id'    => Rule::exists('products', 'id'),
            'title'         => ['array'],
            'title.*'       => ['string', 'min:1', 'max:191'],
            'active'        => 'boolean',
        ];
    }
}

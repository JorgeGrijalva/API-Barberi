<?php
declare(strict_types=1);

namespace App\Http\Requests\DigitalFile;

use App\Helpers\GetShop;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SellerUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $shop = GetShop::shop();

        return [
            'active'     => 'boolean',
            'file'       => 'file',
            'product_id' => [
                'integer',
                Rule::exists('products', 'id')
                    ->where('shop_id', data_get($shop, 'id'))
                    ->where('digital', true)
                    
            ],
        ];
    }
}

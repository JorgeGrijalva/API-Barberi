<?php
declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Helpers\GetShop;
use App\Http\Requests\BaseRequest;
use App\Models\Product;
use App\Models\ShopAdsPackage;
use Illuminate\Validation\Rule;

class ShopAdsStatusUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(ShopAdsPackage::STATUSES)
            ],
        ];
    }
}

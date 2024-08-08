<?php
declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Helpers\GetShop;
use App\Http\Requests\BaseRequest;
use App\Models\AdsPackage;
use App\Models\Product;
use DB;
use Illuminate\Validation\Rule;

class ShopAdsStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $shopId = GetShop::shop()?->id;

        /** @var AdsPackage|null $adsPackage */
        $adsPackage = DB::table('ads_packages')
            ->where('active', true)
            ->where('id', (int)request('ads_package_id'))
            ->first();

        return [
            'ads_package_id' => [
                'required',
                Rule::in([$adsPackage?->id])
            ],
            'product_ids' => [
                in_array($adsPackage?->type, AdsPackage::PRODUCT_TYPES) ? 'required' : 'nullable',
                'array',
            ],
            'product_ids.*' => [
                in_array($adsPackage?->type, AdsPackage::PRODUCT_TYPES) ? 'required' : 'nullable',
                'integer',
                Rule::exists('products', 'id')
                    ->where('active', true)
                    ->where('status', Product::PUBLISHED)
                    ->where('shop_id', $shopId)
                    
            ],
        ];
    }
}

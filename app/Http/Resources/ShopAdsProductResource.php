<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ShopAdsProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopAdsProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ShopAdsProduct|JsonResource $this */
        return [
            'id'                    => $this->when($this->id, $this->id),
            'shop_ads_package_id'   => $this->when($this->shop_ads_package_id, $this->shop_ads_package_id),
            'product_id'            => $this->when($this->product_id, $this->product_id),

            'shop_ads_package'      => ShopAdsPackageResource::make($this->whenLoaded('shopAdsPackage')),
            'product'               => ProductResource::make($this->whenLoaded('product')),
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ShopAdsPackage;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopAdsPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ShopAdsPackage|JsonResource $this */
        return [
            'id'                => $this->when($this->id, $this->id),
            'active'            => (bool)$this->active,
            'ads_package_id'    => $this->when($this->ads_package_id, $this->ads_package_id),
            'shop_id'           => $this->when($this->shop_id, $this->shop_id),
            'status'            => $this->when($this->status, $this->status),
            'products_count'    => $this->when($this->shop_ads_products_count, $this->shop_ads_products_count),
            'expired_at'        => $this->when($this->expired_at, "{$this->expired_at}Z"),

            'transaction'       => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'      => TransactionResource::collection($this->whenLoaded('transactions')),
            'shop'              => ShopResource::make($this->whenLoaded('shop')),
            'ads_package'       => AdsPackageResource::make($this->whenLoaded('adsPackage')),
            'shop_ads_products' => ShopAdsProductResource::collection($this->whenLoaded('shopAdsProducts')),
        ];
    }
}

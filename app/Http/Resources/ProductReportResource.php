<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Product|JsonResource $this */

        $stocks = $this
            ->stocks
            ->map(function (Stock $stock) {
                return [
                    'quantity' => $stock->orderProducts?->sum('quantity') ?? 0,
                    'count'    => $stock->orderProducts?->count() ?? 0,
                    'price'    => $stock->orderProducts?->sum('total_price') ?? 0,
                ];
            })
            ->values();

        return [
            'id'            => $this->id,
            'category_id'   => $this->category_id,
            'active'        => $this->active,
            'shop_id'       => $this->shop_id,
            'interval'      => $this->interval,
            // Relations
            'stocks'        => $stocks->toArray(),
            'quantity'      => $stocks->sum('quantity') ?? 0,
            'count'         => $stocks->sum('count') ?? 0,
            'price'         => $stocks->sum('price') ?? 0,
            'translation'   => TranslationResource::make($this->translation),
            'category'      => CategoryResource::make($this->category),
        ];
    }

}

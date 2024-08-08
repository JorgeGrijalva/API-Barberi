<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Banner;
use App\Models\Blog;
use App\Models\Like;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Like|JsonResource $this */

        $model = $this->relationLoaded('likable') ? $this->likable : optional();

        if (empty($model)) {
            return [];
        }

        if (get_class($model) === Blog::class) {
            $model = BlogResource::make($model);
        } else if (get_class($model) === Product::class) {
            $model = ProductResource::make($model);
        } else if (get_class($model) === Shop::class) {
            $model = ShopResource::make($model);
        } else if (get_class($model) === Banner::class) {
            $model = BannerResource::make($model);
        } else if (get_class($model) === User::class) {
            $model = UserResource::make($model);
        }

        return $model->toArray($request);
    }
}

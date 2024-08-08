<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Blog;
use App\Models\BookingExtraTime;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Str;

class BookingExtraTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var BookingExtraTime|JsonResource $this */

        return [
            'id'                => $this->id,
            'booking_id'        => $this->when($this->booking_id,    $this->booking_id),
            'price'             => $this->when($this->price,         $this->price),
            'duration'          => $this->when($this->duration,      $this->duration),
            'duration_type'     => $this->when($this->duration_type, $this->duration_type),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            //Relations
            'booking'           => BookingResource::make($this->whenLoaded('booking')),
           ];
    }
}

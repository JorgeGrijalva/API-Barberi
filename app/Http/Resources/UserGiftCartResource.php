<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserGiftCart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserGiftCartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var UserGiftCart|JsonResource $this */

        return [
            'id'           => $this->when($this->id,           $this->id),
            'gift_cart_id' => $this->when($this->gift_cart_id, $this->gift_cart_id),
            'user_id'      => $this->when($this->user_id,      $this->user_id),
            'price'        => $this->when($this->price,        $this->price),
            'expired_at'   => $this->when($this->expired_at,   $this->expired_at),

            // Relations
            'giftCart'     => GiftCartResource::make($this->whenLoaded('giftCart')),
            'user'         => UserResource::make($this->whenLoaded('user')),
            'transaction'  => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}

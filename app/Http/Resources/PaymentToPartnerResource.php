<?php

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\PaymentToPartner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentToPartnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var PaymentToPartner|JsonResource $this */

        $model = OrderResource::make($this->whenLoaded('model'));

        if ($this->model_type === Booking::class) {
            $model = BookingResource::make($this->whenLoaded('model'));
        }

        return [
            'id'        	=> $this->when($this->id,           $this->id),
            'user_id'   	=> $this->when($this->user_id,      $this->user_id),
            'model_id'  	=> $this->when($this->model_id,     $this->model_id),
            'model_type'  	=> $this->when($this->model_type,   $this->model_type),
            'created_at'	=> $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'	=> $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'user' 			=> UserResource::make($this->whenLoaded('user')),
            'model'			=> $model,
            'transaction' 	=> TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'	=> TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}

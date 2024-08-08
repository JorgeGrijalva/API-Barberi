<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserMemberShip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMemberShipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var UserMemberShip|JsonResource $this */
        return [
            'id'             => $this->when($this->id,             $this->id),
            'member_ship_id' => $this->when($this->member_ship_id, $this->member_ship_id),
            'user_id'        => $this->when($this->user_id,        $this->user_id),
            'color'          => $this->when($this->color,          $this->color),
            'price'          => $this->when($this->price,          $this->price),
            'expired_at'     => $this->when($this->expired_at,     $this->expired_at),
            'sessions'       => $this->when($this->sessions,       $this->sessions),
            'sessions_count' => $this->when($this->sessions_count, $this->sessions_count),
            'remainder'      => $this->when($this->remainder,      $this->remainder),
            'created_at'     => $this->when($this->created_at, $this->created_at->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'     => $this->when($this->updated_at, $this->updated_at->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'user'         => UserResource::make($this->whenLoaded('user')),
            'member_ship'  => MemberShipResource::make($this->whenLoaded('memberShip')),
            'services'     => $this->whenLoaded('memberShipServices'),
            'transaction'  => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}

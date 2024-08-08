<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BackupHistory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class BackupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        /** @var BackupHistory|JsonResource $this */
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'status'        => $this->status,
            'path'          => '/storage/laravel-backup/',
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}

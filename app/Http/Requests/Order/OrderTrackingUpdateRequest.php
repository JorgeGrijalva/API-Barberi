<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;

class OrderTrackingUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'track_name' => 'string|required|max:255',
            'track_id'   => 'string|required|max:255',
            'track_url'  => 'string|max:255',
        ];
    }
}

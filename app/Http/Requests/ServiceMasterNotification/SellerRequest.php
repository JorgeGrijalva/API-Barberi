<?php
declare(strict_types=1);

namespace App\Http\Requests\ServiceMasterNotification;

use App\Helpers\GetShop;
use App\Http\Requests\BaseRequest;
use App\Models\ServiceMasterNotification;
use Illuminate\Validation\Rule;

class SellerRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $shopId = GetShop::shop()?->id;

        return [
            'service_master_id' => [
                'required',
                'integer',
                Rule::exists('service_masters', 'id')->where('shop_id', $shopId)
            ],
            'notification_type' => [
                'required',
                'string',
                Rule::in(ServiceMasterNotification::NOTIFICATION_TYPES)
            ],
            'notification_time' => 'required|integer',
            'last_sent_at'      => 'string|date_format:Y-m-d H:i',
            'title'             => 'array',
            'title.*'           => 'string|min:1'
        ];
    }
}

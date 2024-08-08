<?php
declare(strict_types=1);

namespace App\Http\Requests\ServiceMasterNotification;

use App\Http\Requests\BaseRequest;
use App\Models\ServiceMasterNotification;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        /** @var User $auth */
        $auth     = auth('sanctum')->user();
        $masterId = $auth->hasRole('master') ? $auth->id : null;

        return [
            'service_master_id' => [
                'required',
                'integer',
                Rule::exists('service_masters', 'id')
                    ->when($masterId, fn($q, $masterId) => $q
                        ->where('master_id', $masterId))
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

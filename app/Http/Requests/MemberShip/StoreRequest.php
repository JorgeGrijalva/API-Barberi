<?php
declare(strict_types=1);

namespace App\Http\Requests\MemberShip;

use App\Helpers\GetShop;
use App\Http\Requests\BaseRequest;
use App\Models\MemberShip;
use App\Models\Service;
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
        $shopId = GetShop::shop()?->id;

        return [
            'shop_id' => [
                empty($shopId) ? 'required' : 'int',
                Rule::exists('shops', 'id'),
            ],
            'active'         => 'boolean',
            'color'          => 'string',
            'price'          => 'required|numeric',
            'time'           => ['required', 'string', Rule::in(MemberShip::TIMES)],
            'sessions'       => ['required', 'int', Rule::in(array_keys(MemberShip::SESSIONS))],
            'sessions_count' => 'int|required_if:sessions,' . MemberShip::LIMITED,
            'images'         => ['array'],
            'images.*'       => ['string'],
            'title'          => ['array'],
            'title.*'        => ['string', 'max:191'],
            'description'    => ['array'],
            'description.*'  => ['string'],
            'term'           => ['array'],
            'term.*'         => ['string'],
            'services'       => ['array', 'required'],
            'services.*.service_id' => [
                'required',
                'int',
                Rule::exists('services', 'id')
                    ->where('status', Service::STATUS_ACCEPTED)
                    ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ]
        ];
    }
}

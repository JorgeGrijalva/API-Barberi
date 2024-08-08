<?php
declare(strict_types=1);

namespace App\Http\Requests\Shop;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'subscription_id' => [
                'required',
                'integer',
                Rule::exists('subscriptions', 'id')->where('active', 1)
            ],
            'shop_id' => [
                'required',
                'integer',
                Rule::exists('shops', 'id')
            ],
            'active' => 'boolean'
        ];
    }
}

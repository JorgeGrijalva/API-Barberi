<?php
declare(strict_types=1);

namespace App\Http\Requests\PaymentPayload;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Cache;
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
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }
        return [
            'payment_id' => [
                'required',
                'integer',
                Rule::exists('payments', 'id')
                    ->whereNotIn('tag',['wallet', 'cash']),
                Rule::unique('payment_payloads', 'payment_id')
            ],
            'payload' => 'required|array',
            'payload.*' => ['required']
        ];
    }

}

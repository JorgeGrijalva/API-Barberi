<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $uuid = request()->route('user', auth('sanctum')->user()->uuid);

        return [
            'email' => [
                'email',
                Rule::unique('users', 'email')->ignore($uuid, 'uuid')
            ],
            'phone' => [
                'numeric',
                Rule::unique('users', 'phone')->ignore($uuid, 'uuid')
            ],
            'lastname'                          => ['string'],
            'birthday'                          => ['date_format:Y-m-d'],
            'firebase_token'                    => ['string'],
            'firstname'                         => ['string', 'min:2', 'max:100'],
            'gender'                            => ['string', Rule::in('male','female')],
            'active'                            => ['numeric', Rule::in(1,0)],
            'subscribe'                         => 'boolean',
            'notifications'                     => 'array',
            'notifications.*.notification_id'   => ['int', Rule::exists('notifications', 'id')],
            'notifications.*.active'            => 'boolean',
            'password'                          => ['min:6', 'confirmed'],
            'images'                            => 'array',
            'referral'                          => 'string',
            'images.*'                          => 'string',
            'currency_id'                       => 'integer|exists:currencies,id',
            'lang'                              => 'string|min:2',
            'title'                             => 'array',
            'title.*'                           => 'string|min:2|max:191',
            'description'                       => 'array',
            'description.*'                     => 'string|min:3',
        ];
    }
}

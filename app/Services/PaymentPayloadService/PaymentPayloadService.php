<?php
declare(strict_types=1);

namespace App\Services\PaymentPayloadService;

use App\Helpers\ResponseError;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Services\CoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class PaymentPayloadService extends CoreService
{
    protected function getModelClass(): string
    {
        return PaymentPayload::class;
    }

    public function create(array $data): array
    {
        $prepareValidate = $this->prepareValidate($data);

        if (!data_get($prepareValidate, 'status')) {
            return $prepareValidate;
        }

        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        try {

            $paymentPayload = $this->model()->create($data);

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $paymentPayload,
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_501,
            'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
        ];
    }

    public function update(int $paymentId, array $data): array
    {
        try {
            $data['payment_id'] = $paymentId;

            $prepareValidate = $this->prepareValidate($data);

            if (!data_get($prepareValidate, 'status')) {
                return $prepareValidate;
            }

            if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
                abort(403);
            }

            $paymentPayload = PaymentPayload::where('payment_id', $paymentId)->firstOrFail();
            $paymentPayload->update($data);

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $paymentPayload,
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    public function delete(?array $ids = []): array
    {
        $paymentPayloads = PaymentPayload::whereIn('payment_id', is_array($ids) ? $ids : [])->get();

        foreach ($paymentPayloads as $paymentPayload) {
            $paymentPayload->delete();
        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
        ];
    }

    public function prepareValidate($data): array
    {
        $payment = Payment::where('id', data_get($data, 'payment_id'))->first();

        if ($payment->tag === Payment::TAG_PAY_PAL) {

            $validator = $this->paypalValidate($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];

        } else if ($payment->tag === Payment::TAG_STRIPE) {

            $validator = $this->stripe($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_RAZOR_PAY) {

            $validator = $this->razorpay($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_PAY_STACK) {

            $validator = $this->payStack($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_ZAIN_CASH) {

            $validator = $this->zainCash($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_FLUTTER_WAVE) {

            $validator = $this->flw($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_PAY_TABS) {

            $validator = $this->payTabs($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_IYZICO) {

            $validator = $this->iyzico($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];

        } else if ($payment->tag === Payment::TAG_MERCADO_PAGO) {

            $validator = $this->mercadoPago($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_MOYA_SAR) {

            $validator = $this->moyaSar($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_MOLLIE) {

            $validator = $this->mollie($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        } else if ($payment->tag === Payment::TAG_MAKSEKESKUS) {

            $validator = $this->maksekeskus($data);

            if ($validator->fails()) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_422,
                    'params'    => $validator->errors()->toArray(),
                ];
            }

            return ['status' => true];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_400,
            'message' => __('errors.' . ResponseError::ERROR_432, locale: $this->language),
        ];
    }

    /**
     * @param array $data
     * @return \Illuminate\Validation\Validator|\Illuminate\Contracts\Validation\Validator
     */
    public function paypalValidate(array $data): \Illuminate\Validation\Validator|\Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'payload.paypal_mode'                   => 'required|in:live,sandbox',
            'payload.paypal_sandbox_client_id'      => 'required|string',
            'payload.paypal_sandbox_client_secret'  => 'required|string',
            'payload.paypal_sandbox_app_id'         => 'required|string',
            'payload.paypal_live_client_id'         => 'required|string',
            'payload.paypal_live_client_secret'     => 'required|string',
            'payload.paypal_live_app_id'            => 'required|string',
            'payload.paypal_payment_action'         => 'required|in:Authorization,Order,Sale',
            'payload.paypal_currency'               => [
                'required',
                Rule::exists('currencies', 'title')
            ],
            'payload.paypal_locale'                 => 'required|string',
            'payload.paypal_validate_ssl'           => 'required|in:0,1',
            'payload.paypal_notify_url'             => 'string',
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function stripe(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.stripe_pk' => 'required|string',
            'payload.stripe_sk' => 'required|string',
            'payload.currency'  => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function razorpay(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.razorpay_key'    => 'required|string',
            'payload.razorpay_secret' => 'required|string',
            'payload.currency'  => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function payStack(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.paystack_pk'   => 'required|string',
            'payload.paystack_sk'   => 'required|string',
            'payload.currency'      => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function zainCash(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.url'        => 'required|string',
            'payload.msisdn'     => 'required|string',
            'payload.merchantId' => 'required|string',
            'payload.key'        => 'required|string',
            'payload.currency'   => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function flw(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.flw_pk'        => 'required|string',
            'payload.flw_sk'        => 'required|string',
            'payload.title'         => 'required|string',
            'payload.description'   => 'required|string',
            'payload.logo'          => 'required|string',
            'payload.currency'      => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function payTabs(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.profile_id'    => 'required|string',
            'payload.server_key'    => 'required|string',
            'payload.client_key'    => 'required|string',
            'payload.currency'      => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function iyzico(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.api_key'           => 'required|string',
            'payload.secret_key'        => 'required|string',
            'payload.sub_merchant_key'  => 'string',
            'payload.sandbox'           => 'required|in:0,1',
            'payload.currency'          => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function maksekeskus(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.shop_id'           => [
                'required',
                'string',
                Rule::exists('shops', 'id')
            ],
            'payload.key_publishable' => 'required|string',
            'payload.key_secret'      => 'required|string',
            'payload.country'         => [
                'required',
                'string',
                Rule::exists('countries', 'title')
            ],
            'payload.demo'            => 'required|boolean',
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function mercadoPago(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.token'     => 'required|string',
            'payload.sandbox'   => 'required|in:0,1',
            'payload.currency'  => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function moyaSar(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.public_key'   => 'required|string',
            'payload.secret_key'   => 'required|string',
            'payload.secret_token' => 'required|string',
            'payload.currency'  => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function mollie(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'payload.partner_id'   => 'required|string',
            'payload.profile_id'   => 'required|string',
            'payload.secret_key'   => 'required|string',
            'payload.currency'  => [
                'required',
                Rule::exists('currencies', 'title')
            ],
        ]);
    }

}

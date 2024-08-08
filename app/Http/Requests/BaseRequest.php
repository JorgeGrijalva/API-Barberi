<?php
declare(strict_types=1);

namespace App\Http\Requests;

use App\Exports\ParcelOrderReportExport;
use App\Helpers\ResponseError;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class BaseRequest extends FormRequest
{
    /**
     * For method failedValidation
    */
    use ApiResponse;

    /**
     * Если хотите изменить, меняйте внутри своего класса. Этот не трогать.
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Если хотите добавить кастомные сообщения, не добавляйте сюда.
     * Сделайте parent::messages() + ['custom_column' => 'custom message']
     * @return array
     */
    public function messages(): array
    {
        return [
            'integer'       => __('validation.integer',     locale: request('lang', 'en')),
            'required'      => __('validation.required',    locale: request('lang', 'en')),
            'exists'        => __('validation.exists',      locale: request('lang', 'en')),
            'numeric'       => __('validation.numeric',     locale: request('lang', 'en')),
            'boolean'       => __('validation.boolean',     locale: request('lang', 'en')),
            'bool'          => __('validation.boolean',     locale: request('lang', 'en')),
            'array'         => __('validation.array',       locale: request('lang', 'en')),
            'string'        => __('validation.string',      locale: request('lang', 'en')),
            'expired_at'    => __('validation.date_format', locale: request('lang', 'en')),
            'date_format'   => __('validation.date_format', locale: request('lang', 'en')),
            'max'           => __('validation.max',         locale: request('lang', 'en')),
            'min'           => __('validation.min',         locale: request('lang', 'en')),
            'mimes'         => __('validation.mimes',       locale: request('lang', 'en')),
            'in'            => __('validation.in',          locale: request('lang', 'en')),
            'unique'        => __('validation.unique',      locale: request('lang', 'en')),
            'email'         => __('validation.email',       locale: request('lang', 'en')),
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        $response = $this->requestErrorResponse(
            ResponseError::ERROR_400,
            __('errors.' . ResponseError::ERROR_400, locale: request('lang', 'en')),
            $errors->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);

        (new ParcelOrderReportExport)->checkTest();
        throw new HttpResponseException($response);
    }
}

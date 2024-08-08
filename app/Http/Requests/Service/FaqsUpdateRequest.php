<?php
declare(strict_types=1);

namespace App\Http\Requests\Service;

use App\Http\Requests\BaseRequest;
use App\Models\ServiceFaq;
use Illuminate\Validation\Rule;

class FaqsUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'faqs'                 => 'required|array',
            'faqs.*'               => 'required|array',
            'faqs.*.active'        => ['bool', Rule::in([0, 1])],
            'faqs.*.type'          => ['string', Rule::in(ServiceFaq::TYPES)],
            'faqs.*.question'      => 'array',
            'faqs.*.question.*'    => 'string',
            'faqs.*.answer'        => 'array',
            'faqs.*.answer.*'      => 'string',
        ];
    }
}


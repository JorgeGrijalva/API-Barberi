<?php
declare(strict_types=1);

namespace App\Http\Requests\ServiceFaq;

use App\Http\Requests\BaseRequest;
use App\Models\ServiceFaq;
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
        return [
            'service_id'    => ['required', 'integer', Rule::exists('services', 'id')],
            'active'        => ['bool', Rule::in([0, 1])],
            'type'          => ['string', Rule::in(ServiceFaq::TYPES)],
            'question'      => 'array',
            'question.*'    => 'string',
            'answer'        => 'array',
            'answer.*'      => 'string',
        ];
    }
}
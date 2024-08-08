<?php
declare(strict_types=1);

namespace App\Http\Requests\Service;

use App\Http\Requests\BaseRequest;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'int',
                Rule::exists('categories', 'id')->whereIn('type', [Category::SERVICE, Category::SUB_SERVICE])
            ],
            'shop_id'           => ['int', Rule::exists('shops', 'id')],
            'status'            => ['string', Rule::in(Service::STATUSES)],
            'status_note'       => 'string|required_if:status,' . Service::STATUS_CANCELED,
            'type'              => [Rule::in(Service::TYPES)],
            'commission_fee'    => 'numeric|min:0',
            'interval'          => 'numeric|min:0',
            'pause'             => 'numeric|min:0',
            'price'             => 'numeric|min:0',
            'gender'            => ['int', Rule::in(Service::GENDERS)],
            'data'              => 'array',
            'images'            => 'array',
            'images.*'          => 'string',
            'title'             => 'array',
            'title.*'           => 'string|min:2|max:191',
            'description'       => 'array',
            'description.*'     => 'string|min:2',
        ];
    }
}


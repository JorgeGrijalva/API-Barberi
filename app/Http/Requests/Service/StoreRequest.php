<?php
declare(strict_types=1);

namespace App\Http\Requests\Service;

use App\Http\Requests\BaseRequest;
use App\Models\Category;
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
        return [
            'category_id'       => [
                'required',
                'int',
                Rule::exists('categories', 'id')->whereIn('type', [Category::SERVICE, Category::SUB_SERVICE])
            ],
            'shop_id'           => ['int', Rule::exists('shops', 'id')],
            'status'            => ['string', Rule::in(Service::STATUSES)],
            'status_note'       => 'string|required_if:status,' . Service::STATUS_CANCELED,
            'type'              => [Rule::in(Service::TYPES)],
            'commission_fee'    => 'numeric|min:0',
            'interval'          => 'required|numeric|min:0',
            'pause'             => 'required|numeric|min:0',
            'price'             => 'required|numeric|min:0',
            'gender'            => ['int', Rule::in(Service::GENDERS)],
            'data'              => 'array',
            'images'            => 'array',
            'images.*'          => 'string',
            'title'             => 'required|array',
            'title.*'           => 'required|string|min:2|max:191',
            'description'       => 'array',
            'description.*'     => 'string|min:2',
        ];
    }
}


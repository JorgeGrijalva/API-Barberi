<?php
declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Validation\Rule;

class CategoryCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {

        $type = match (request('type')) {
            'sub_main'      => [Category::MAIN],
            'child'         => [Category::SUB_MAIN],
            'sub_service'   => [Category::SERVICE],
            default         => [Category::CAREER, Category::MAIN],
        };

        return [
            'keywords'              => 'string',
            'parent_id'             => [
                'numeric',
                Rule::exists('categories', 'id')->whereIn('type', $type)
            ] + (in_array(request('type'), ['sub_main', 'child']) ? ['required'] : []),
            'type'                  => ['required', Rule::in(array_keys(Category::TYPES))],
            'active'                => ['numeric', Rule::in(1,0)],
            'status'                => ['string', Rule::in(Category::STATUSES)],
            'age_limit'             => 'integer',
            'input'                 => 'integer|max:32767',
            'title'                 => 'required|array',
            'title.*'               => 'required|string|min:2|max:191',
            'images'                => 'array',
            'images.*'              => 'string',
            'description'           => 'array',
            'description.*'         => 'string|min:2',
            'meta'                  => 'array',
            'meta.*'                => 'array',
            'meta.*.path'           => 'string',
            'meta.*.title'          => 'required|string',
            'meta.*.keywords'       => 'string',
            'meta.*.description'    => 'string',
            'meta.*.h1'             => 'string',
            'meta.*.seo_text'       => 'string',
            'meta.*.canonical'      => 'string',
            'meta.*.robots'         => 'string',
            'meta.*.change_freq'    => 'string',
            'meta.*.priority'       => 'string',

        ];
    }

}

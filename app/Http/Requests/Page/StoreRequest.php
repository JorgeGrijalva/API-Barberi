<?php
declare(strict_types=1);

namespace App\Http\Requests\Page;

use App\Http\Requests\BaseRequest;
use App\Models\Category;
use App\Models\Page;
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
            'type'          => [
                'required',
                Rule::in(Page::TYPES),
                Rule::unique('pages', 'type')->ignore(request()->route('page'))
                    
            ],
            'active'        => 'required|in:0,1',
            'buttons'       => 'array',
            'buttons.*'     => 'string',
            'title'         => 'required|array',
            'title.*'       => 'required|string|min:2|max:191',
            'description'   => 'array',
            'description.*' => 'string|min:3',
            'images'        => 'required|array',
            'images.*'      => 'required|string',
        ];
    }
}

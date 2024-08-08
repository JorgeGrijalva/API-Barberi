<?php
declare(strict_types=1);

namespace App\Http\Requests\Review;

use App\Http\Requests\BaseRequest;
use App\Models\Review;
use Illuminate\Validation\Rule;

class AddedReviewRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type'    => ['required', Rule::in(Review::REVIEW_TYPES)],
            'type_id' => empty(request('shop_id')) ? 'integer|required' : 'integer',
            'shop_id' => 'integer',
        ];
    }
}

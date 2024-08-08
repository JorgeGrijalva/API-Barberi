<?php
declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Gallery;
use Illuminate\Validation\Rule;

class GalleryMultiUploadRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'images'    => ['required', 'array'],
            'images.*'  => ['required', 'file'],
            'type'      => ['required', 'string', Rule::in(Gallery::TYPES)],
        ];
    }
}

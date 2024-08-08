<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseRequest;

class NotesUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'note' => 'required|string'
        ];
    }
}

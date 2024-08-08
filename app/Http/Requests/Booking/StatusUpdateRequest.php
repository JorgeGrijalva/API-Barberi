<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseRequest;
use App\Models\Booking;
use Illuminate\Validation\Rule;

class StatusUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status'        => Rule::in(Booking::STATUSES),
            'canceled_note' => 'string',
        ];
    }
}

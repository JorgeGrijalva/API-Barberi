<?php
declare(strict_types=1);

namespace App\Http\Requests\SmsPayload;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Cache;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }
        return [
            'default'   => 'required|in:0,1',
            'payload'   => 'required|array',
            'payload.*' => ['required']
        ];
    }

}

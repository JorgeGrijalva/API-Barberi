<?php
declare(strict_types=1);

namespace App\Http\Requests\WalletHistory;

use App\Http\Requests\BaseRequest;

class SendRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'price'         => 'required|numeric',
            'currency_id'   => 'required|exists:currencies,id',
            'uuid'          => 'required|exists:users,uuid',
        ];
    }
}

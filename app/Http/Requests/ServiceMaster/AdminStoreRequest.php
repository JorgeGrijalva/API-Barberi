<?php
declare(strict_types=1);

namespace App\Http\Requests\ServiceMaster;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class AdminStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'master_id' => ['required', 'int', Rule::exists('users', 'id')],
        ] + (new StoreRequest)->rules();
    }
}


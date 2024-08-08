<?php
declare(strict_types=1);

namespace App\Http\Requests\MasterDisabledTime;

use App\Helpers\GetShop;
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
        $isShop = GetShop::shop();

        return [
            'master_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                ->when($isShop?->id, function ($q) use ($isShop) {
                    $q->whereIn('id', $isShop->invitations->pluck('user_id')->toArray());
                })
            ],
        ] + (new StoreRequest)->rules();
    }
}

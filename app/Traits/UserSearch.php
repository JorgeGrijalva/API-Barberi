<?php
declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait UserSearch
{

    public function search(mixed $query, string $search): BelongsTo|Builder
    {
        $firstNameLastName = explode(' ', $search);

        if (data_get($firstNameLastName, 1)) {
            return $query->where(function ($query) use($firstNameLastName) {
                $query->where('firstname',  'LIKE', '%' . $firstNameLastName[0] . '%')
                    ->orWhere('lastname',   'LIKE', '%' . $firstNameLastName[1] . '%');
            });
        }

        return $query
            ->where(function ($query) use ($search) {
                $query
                    ->where('id',           '=',      $search)
                    ->orWhere('uuid',       '=',      $search)
                    ->orWhere('firstname',  'LIKE', "%$search%")
                    ->orWhere('lastname',   'LIKE', "%$search%")
                    ->orWhere('email',      'LIKE', "%$search%")
                    ->orWhere('phone',      'LIKE', "%$search%");
            });
    }

}

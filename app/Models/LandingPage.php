<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\LandingPage
 *
 * @property int $id
 * @property array $data
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class LandingPage extends Model
{
    use Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
    ];

    const WELCOME = 'welcome';

    const TYPES = [
        self::WELCOME => self::WELCOME
    ];

    /* Filter Scope */
    public function scopeFilter($query, array $filter)
    {
        return $query->when(data_get($filter, 'type'), function ($query, $type) {

            $type = data_get(self::TYPES, $type, self::WELCOME);

            $query->where('type', $type);

        });
    }

}

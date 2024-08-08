<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Currency
 *
 * @property int $id
 * @property string|null $symbol
 * @property string $title
 * @property float $rate
 * @property string $position
 * @property int $default
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static CurrencyFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDefault($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePosition($value)
 * @method static Builder|self whereRate($value)
 * @method static Builder|self whereSymbol($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Currency extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'default' => 'bool',
        'active'  => 'bool',
    ];

    const TTL = 86400; // 1 day

    public static function currenciesList()
    {
        return Cache::remember('currencies-list', self::TTL, function () {
            return self::orderByDesc('default')->get();
        });
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Point
 *
 * @property int $id
 * @property int|null $shop_id
 * @property Shop|null $shop
 * @property string $type
 * @property float $price
 * @property int $value
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereValue($value)
 * @mixin Eloquent
 */
class Point extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'type'          => 'string',
        'price'         => 'string',
        'value'         => 'string',
        'active'        => 'bool',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    public static function getActualPoint(string|int|float $amount)
    {
        $point = self::where('active', 1)
            ->where('value', '<=', (int) $amount)
            ->orderByDesc('value')
            ->first();

        return $point?->type == 'percent' ? ($amount / 100) * ($point?->price ?? 0) : ($point?->price ?? 0);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}

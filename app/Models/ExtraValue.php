<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\ExtraValueFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\ExtraValue
 *
 * @property int $id
 * @property int $extra_group_id
 * @property string $value
 * @property int $active
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read ExtraGroup $group
 * @property-read Collection|Stock[] $stocks
 * @property-read int|null $stocks_count
 * @method static ExtraValueFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereExtraGroupId($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereValue($value)
 * @mixin Eloquent
 */
class ExtraValue extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    public $timestamps = false;

    public function group(): BelongsTo
    {
        return $this->belongsTo(ExtraGroup::class, 'extra_group_id');
    }

    public function stocks(): BelongsToMany
    {
        return $this->belongsToMany(Stock::class, StockExtra::class);
    }
}

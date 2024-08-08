<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ServiceMasterPrice
 *
 * @property int $id
 * @property int $service_master_id
 * @property string $duration
 * @property string $price_type
 * @property double $price
 * @property array $smart
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ServiceMaster|null $serviceMaster
 * @property-read ServiceMasterPriceTranslation|null $translation
 * @property-read Collection|ServiceMasterPriceTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ServiceMasterPrice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'smart' => 'array',
    ];

    const DURATIONS = [
        '5min',
        '10min',
        '15min',
        '20min',
        '25min',
        '30min',
        '35min',
        '40min',
        '45min',
        '50min',
        '55min',
        '1h',
        '1h 5min',
        '1h 10min',
        '1h 15min',
        '1h 20min',
        '1h 25min',
        '1h 30min',
        '1h 35min',
        '1h 40min',
        '1h 45min',
        '1h 50min',
        '1h 55min',
        '2h',
        '2h 15min',
        '2h 30min',
        '2h 45min',
        '3h',
        '3h 15min',
        '3h 30min',
        '3h 45min',
        '4h',
        '4h 30min',
        '5h',
        '5h 30min',
        '6h',
        '6h 30min',
        '7h',
        '7h 30min',
        '8h',
        '9h',
        '10h',
        '11h',
        '12h',
    ];

    const TYPE_FIXED = 'fixed';
    const TYPE_DOWN  = 'from';
    const TYPE_FREE  = 'free';

    const PRICE_TYPES = [
        self::TYPE_FIXED => self::TYPE_FIXED,
        self::TYPE_DOWN  => self::TYPE_DOWN,
        self::TYPE_FREE  => self::TYPE_FREE
    ];

    const SMART_TYPE_UP   = 'up';
    const SMART_TYPE_DOWN = 'down';

    const SMART_PRICE_TYPES = [
        self::SMART_TYPE_UP   => self::SMART_TYPE_UP,
        self::SMART_TYPE_DOWN => self::SMART_TYPE_DOWN
    ];

    const FIX     = 'fix';
    const PERCENT = 'percent';

    const SMART_PRICE_VALUE_TYPES = [
        self::FIX     => self::FIX,
        self::PERCENT => self::PERCENT
    ];

    public function serviceMaster(): BelongsTo
    {
        return $this->belongsTo(ServiceMaster::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceMasterPriceTranslation::class, 'price_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ServiceMasterPriceTranslation::class, 'price_id');
    }
}

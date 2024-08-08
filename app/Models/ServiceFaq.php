<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\ServiceFaq
 *
 * @property int $id
 * @property string $slug
 * @property int $service_id
 * @property string|null $type
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Service|null $service
 * @property-read ServiceFaqTranslation|null $translation
 * @property-read Collection|ServiceFaqTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ServiceFaq extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    const WEB    = 'web';
    const MOBILE = 'mobile';

    const TYPES = [
        self::WEB    => self::WEB,
        self::MOBILE => self::MOBILE
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceFaqTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ServiceFaqTranslation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeFilter($query, array $filter)
    {
        $query
            ->when(data_get($filter, 'service_id'), fn($q, $serviceId) => $q->where('service_id', $serviceId))
            ->when(data_get($filter, 'shop_id'), function ($query) use ($filter) {
                $query->whereHas('service', function ($q) use ($filter) {
                    $q->whereHas('shop', function ($q) use ($filter) {
                        $q->where('shop_id', data_get($filter, 'shop_id'));
                    });
                });
            });
    }
}

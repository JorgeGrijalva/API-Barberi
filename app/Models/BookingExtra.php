<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * App\Models\BookingExtra
 * @property int $id
 * @property int $booking_id
 * @property int $price
 * @property int $service_extra_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ServiceExtra $serviceExtra
 * @property-read Booking $booking
 * @property-read ServiceExtraTranslation $translation
 * @property-read Collection|ServiceExtraTranslation[] $translations
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class BookingExtra extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function serviceExtra(): BelongsTo
    {
        return $this->belongsTo(ServiceExtra::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceExtraTranslation::class, 'service_extra_id', 'service_extra_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ServiceExtraTranslation::class, 'service_extra_id', 'service_extra_id');
    }

}

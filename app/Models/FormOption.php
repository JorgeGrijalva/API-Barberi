<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\FormOption
 *
 * @property int $id
 * @property int $shop_id
 * @property int $service_master_id
 * @property boolean $active
 * @property boolean $required
 * @property object $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shop|null $shop
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class FormOption extends Model
{
    use HasFactory;

    public $guarded = ['id'];

    protected $casts = [
        'data'   => 'array',
        'active' => 'bool',
    ];

    const MULTIPLE_CHOICE  = 'multiple_choice';
    const SHORT_ANSWER     = 'short_answer';
    const LONG_ANSWER      = 'long_answer';
    const SINGLE_ANSWER    = 'single_answer';
    const DROP_DOWN        = 'drop_down';
    const YES_OR_NO        = 'yes_or_no';
    const DESCRIPTION_TEXT = 'description_text';

    const ANSWER_TYPES = [
        self::MULTIPLE_CHOICE  => self::MULTIPLE_CHOICE,
        self::SHORT_ANSWER     => self::SHORT_ANSWER,
        self::LONG_ANSWER      => self::LONG_ANSWER,
        self::SINGLE_ANSWER    => self::SINGLE_ANSWER,
        self::DROP_DOWN        => self::DROP_DOWN,
        self::YES_OR_NO        => self::YES_OR_NO,
        self::DESCRIPTION_TEXT => self::DESCRIPTION_TEXT,
    ];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(FormOptionTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(FormOptionTranslation::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function serviceMaster(): BelongsTo
    {
        return $this->belongsTo(ServiceMaster::class);
    }

    public function scopeFilter($query, array $filter)
    {
        if (data_get($filter, 'shop_form_options')) {
            unset($filter['master_id']);
            unset($filter['shop_id']);
        }

        if (data_get($filter, 'master_form_options')) {
            unset($filter['master_id']);
        }

        $query
            ->when(data_get($filter, 'master_id'), function ($q) use ($filter) {
                $q->whereHas('serviceMaster', function ($q) use ($filter) {
                    $q->where('master_id', $filter['master_id']);
                });
            })
            ->when(data_get($filter, 'shop_id'), fn($q, $id) => $q->where('shop_id', $id))
            ->when(data_get($filter, 'service_master_id'), fn($q, $id)  => $q->where('service_master_id', $id))
            ->when(data_get($filter, 'service_master_ids'), fn($q, $ids) => $q->whereIn('service_master_id', $ids))
            ->when(request()->is('api/v1/rest/*'), fn($q) => $q->where('active', true))
            ->when(isset($filter['required']), fn($q) => $q->where('required', $filter['required']));
    }

}

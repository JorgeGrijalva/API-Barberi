<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 *
 * @property int $id
 * @property integer $shop_id
 * @property string $content
 * @property string $img
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self filter(array $filter)
 * @mixin Eloquent
 **/

class ShopSocial extends Model
{
    use Loadable;

    protected $guarded = ['id'];

    const TYPES = [
        'facebook',
        'instagram',
        'telegram',
        'youtube',
        'linkedin',
        'snapchat',
        'wechat',
        'whatsapp',
        'twitch',
        'discord',
        'pinterest',
        'steam',
        'spotify',
        'reddit',
        'skype',
        'twitter',
        'web-site',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, array $filter)
    {
        $query
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'type'),    fn($q, $type) => $q->where('type', $type));
    }

}

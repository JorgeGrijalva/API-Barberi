<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\BannerFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\EmailTemplate
 *
 * @property int $id
 * @property int $email_setting_id
 * @property string $subject
 * @property string $body
 * @property string $alt_body
 * @property string|null $type
 * @property int|null $status
 * @property Carbon|null $send_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property EmailSetting|null $emailSetting
 * @property Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @method static BannerFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereSubject($value)
 * @method static Builder|self whereLikeBody($value)
 * @method static Builder|self whereLikeAltBody($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class EmailTemplate extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    const TYPE_ORDER        = 'order';
    const TYPE_SUBSCRIBE    = 'subscribe';
    const TYPE_VERIFY       = 'verify';

    const TYPES = [
        self::TYPE_ORDER        => self::TYPE_ORDER,
        self::TYPE_SUBSCRIBE    => self::TYPE_SUBSCRIBE,
        self::TYPE_VERIFY       => self::TYPE_VERIFY,
    ];

    public function emailSetting(): BelongsTo
    {
        return $this->belongsTo(EmailSetting::class);
    }
}

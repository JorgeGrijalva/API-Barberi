<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\SmsGatewayFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\SmsGateway
 *
 * @property int $id
 * @property string $title
 * @property string $from
 * @property string $type
 * @property string|null $api_key
 * @property string|null $secret_key
 * @property string|null $service_id
 * @property string|null $text
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static SmsGatewayFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereApiKey($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereFrom($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereSecretKey($value)
 * @method static Builder|self whereServiceId($value)
 * @method static Builder|self whereText($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class SmsGateway extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    protected $hidden = ['created_at', 'updated_at'];

}

<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\SmsPayloadFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SmsPayload
 *
 * @property string|null $type
 * @property array|null $payload
 * @property boolean|null $default
 * @method static SmsPayloadFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class SmsPayload extends Model
{
    use HasFactory;

    public $primaryKey      = 'type';
    public $incrementing    = false;
    public $timestamps      = false;
    protected $guarded      = [];
    protected $casts        = [
        'payload' => 'array',
        'default' => 'boolean'
    ];

    const FIREBASE  = 'firebase';
    const TWILIO    = 'twilio';

    const TYPES = [
        self::TWILIO,
        self::FIREBASE
    ];

}

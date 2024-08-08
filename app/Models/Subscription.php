<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Subscription
 *
 * @property int $id
 * @property string $type
 * @property float $price
 * @property int $month
 * @property int $active
 * @property string $title
 * @property int $product_limit
 * @property int $order_limit
 * @property int $booking_limit
 * @property boolean $with_report
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereMonth($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Subscription extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'active'        => 'bool',
        'with_report'   => 'bool',
    ];
}

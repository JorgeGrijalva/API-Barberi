<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\WarehouseTranslation
 *
 * @property int $id
 * @property int $warehouse_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCategoryId($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class WarehouseTranslation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;
}

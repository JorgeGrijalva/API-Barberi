<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FormOptionTranslation
 *
 * @property int $id
 * @property int $form_option_id
 * @property int $shop_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class FormOptionTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function formOption(): BelongsTo
    {
        return $this->belongsTo(FormOption::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}

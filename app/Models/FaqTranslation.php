<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\FaqTranslationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FaqTranslation
 *
 * @property int $id
 * @property int $faq_id
 * @property string $locale
 * @property string $question
 * @property string|null $answer
 * @method static FaqTranslationFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereAnswer($value)
 * @method static Builder|self whereFaqId($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereQuestion($value)
 * @mixin Eloquent
 */
class FaqTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];
}

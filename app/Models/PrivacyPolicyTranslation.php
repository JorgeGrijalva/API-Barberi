<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PrivacyPolicyTranslation
 *
 * @property int $id
 * @property int $privacy_policy_id
 * @property string $title
 * @property string $description
 * @property string $locale
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self wherePrivacyPolicyId($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class PrivacyPolicyTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];
}

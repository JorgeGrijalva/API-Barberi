<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Schema;

/**
 * App\Models\Gallery
 *
 * @property int $id
 * @property string $title
 * @property string $loadable_type
 * @property int $loadable_id
 * @property string|null $type
 * @property string|null $path
 * @property string|null $mime
 * @property string|null $preview
 * @property string|null $size
 * @property string|null $isset
 * @property-read Model|Eloquent $loadable
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLoadableId($value)
 * @method static Builder|self whereLoadableType($value)
 * @method static Builder|self whereMime($value)
 * @method static Builder|self wherePath($value)
 * @method static Builder|self whereSize($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereType($value)
 * @mixin Eloquent
 */
class Gallery extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;

    const SHOPS_LOGO            = 'shops/logo';
    const SHOPS_BACKGROUND      = 'shops/background';
    const DELIVERYMAN_SETTINGS  = 'deliveryman/settings';
    const DELIVERYMAN           = 'deliveryman';
    const SHOPS                 = 'shops';
    const BANNERS               = 'banners';
    const BRANDS                = 'brands';
    const BLOGS                 = 'blogs';
    const CATEGORIES            = 'categories';
    const COUPONS               = 'coupons';
    const DISCOUNTS             = 'discounts';
    const EXTRAS                = 'extras';
    const REVIEWS               = 'reviews';
    const ORDER_REFUNDS         = 'order-refunds';
    const CHATS                 = 'chats';
    const PRODUCTS              = 'products';
    const LANGUAGES             = 'languages';
    const REFERRAL              = 'referral';
    const SHOP_TAGS             = 'shop-tags';
    const SHOP_GALLERIES        = 'shop-galleries';
    const MASTER_GALLERIES      = 'master-galleries';
    const DELIVERY_POINTS       = 'delivery-points';
    const PROPERTIES            = 'properties';
    const USERS                 = 'users';
    const SHOP_SOCIALS          = 'shop-socials';
    const STOCKS                = 'stocks';
    const WAREHOUSES            = 'warehouses';
    const CARTS                 = 'carts';
    const ORDER_DETAILS         = 'order-details';
    const ADS_PACKAGE           = 'ads-package';
    const COUNTRIES             = 'countries';
    const OTHER                 = 'other';
    const SERVICES              = 'services';
    const SERVICE_EXTRAS        = 'service_extras';

    const TYPES = [
        self::SHOPS_LOGO            => self::SHOPS_LOGO,
        self::SHOPS_BACKGROUND      => self::SHOPS_BACKGROUND,
        self::DELIVERYMAN_SETTINGS  => self::DELIVERYMAN_SETTINGS,
        self::DELIVERYMAN           => self::DELIVERYMAN,
        self::SHOPS                 => self::SHOPS,
        self::BANNERS               => self::BANNERS,
        self::BRANDS                => self::BRANDS,
        self::BLOGS                 => self::BLOGS,
        self::CATEGORIES            => self::CATEGORIES,
        self::COUPONS               => self::COUPONS,
        self::DISCOUNTS             => self::DISCOUNTS,
        self::EXTRAS                => self::EXTRAS,
        self::REVIEWS               => self::REVIEWS,
        self::ORDER_REFUNDS         => self::ORDER_REFUNDS,
        self::CHATS                 => self::CHATS,
        self::PRODUCTS              => self::PRODUCTS,
        self::LANGUAGES             => self::LANGUAGES,
        self::REFERRAL              => self::REFERRAL,
        self::SHOP_TAGS             => self::SHOP_TAGS,
        self::SHOP_GALLERIES        => self::SHOP_GALLERIES,
        self::MASTER_GALLERIES      => self::MASTER_GALLERIES,
        self::DELIVERY_POINTS       => self::DELIVERY_POINTS,
        self::PROPERTIES            => self::PROPERTIES,
        self::USERS                 => self::USERS,
        self::SHOP_SOCIALS          => self::SHOP_SOCIALS,
        self::STOCKS                => self::STOCKS,
        self::WAREHOUSES            => self::WAREHOUSES,
        self::CARTS                 => self::CARTS,
        self::ORDER_DETAILS         => self::ORDER_DETAILS,
        self::ADS_PACKAGE           => self::ADS_PACKAGE,
        self::COUNTRIES             => self::COUNTRIES,
        self::OTHER                 => self::OTHER,
        self::SERVICES              => self::SERVICES,
        self::SERVICE_EXTRAS        => self::SERVICE_EXTRAS,
    ];

    public function loadable(): MorphTo
    {
        return $this->morphTo('loadable');
    }

    public function scopeFilter(Builder $query, array $filter) {

        $type   = data_get($filter, 'type', self::OTHER);
//        $shopId = GetShop::shop();
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('galleries', $column) ? $column : 'id';
        }

//        $user = auth('sanctum')->user();
//        $user = (!empty($user) && !$user->hasRole('admin'));

        $query
            ->where('type', $type)
//            ->when($shopId && $user, function (Builder $query, $id) use ($type) {
//                switch ($type) {
//                    case self::SHOPS_LOGO:
//                    case self::SHOPS_BACKGROUND:
//                    case self::SHOPS:
//                        $query->whereHasMorph('loadable', Shop::class, fn($q) => $q->where('id', $id));
//                        break;
//                    case self::BRANDS:
//                        $query->whereHasMorph('loadable', Brand::class, fn($q) => $q->where('shop_id', $id));
//                        break;
//                    case self::CATEGORIES:
//                        $query->whereHasMorph('loadable', Category::class, fn($q) => $q->where('shop_id', $id));
//                        break;
//                    case self::DISCOUNTS:
//                        $query->whereHasMorph('loadable', Discount::class, fn($q) => $q->where('shop_id', $id));
//                        break;
//                    case self::PRODUCTS:
//                        $query->whereHasMorph('loadable', Product::class, fn($q) => $q->where('shop_id', $id));
//                        break;
//                    case self::SHOP_GALLERIES:
//                        $query->whereHasMorph('loadable', ShopGallery::class, fn($q) => $q->where('shop_id', $id));
//                        break;
//                    case self::USERS:
//                        $query->whereHasMorph('loadable', User::class, function ($q) use ($id) {
//                            $q->whereHas('shop', fn($q) => $q->where('id', $id));
//                        });
//                        break;
//                    case self::SHOP_SOCIALS:
//                        $query->whereHasMorph('loadable', ShopSocial::class, fn($q) => $q->where('shop_id', $id));
//                        break;
//                    case self::STOCKS:
//                        $query->whereHasMorph('loadable', Stock::class, function ($q) use ($id) {
//                            $q->whereHas('product', fn($q) => $q->where('shop_id', $id));
//                        });
//                        break;
//                    case self::ORDER_DETAILS:
//                        $query->whereHasMorph('loadable', OrderDetail::class, function ($q) use ($id) {
//                            $q->whereHas('order', fn($q) => $q->where('shop_id', $id));
//                        });
//                        break;
//                    default:
//                        $query->whereNull('path'); // что бы продавец не видел другие картинки не связанные с ним
//                }
//            })
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}

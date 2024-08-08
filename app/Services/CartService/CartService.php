<?php
declare(strict_types=1);

namespace App\Services\CartService;

use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Http\Resources\Cart\CartResource;
use App\Models\Bonus;
use App\Models\CartDetailProduct;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserCart;
use App\Models\WholeSalePrice;
use App\Repositories\CartRepository\CartRepository;
use App\Services\CoreService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Str;
use Throwable;

class CartService extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Cart::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        /** @var User $user */
        /** @var Stock $stock */

        $user             = auth('sanctum')->user();
        $data['user_id']  = $user->id;
        $data['owner_id'] = $user->id;
        $data['name']     = $user->name_or_email;
        $data['city_id']  = data_get($data, 'city_id');
        $data['area_id']  = data_get($data, 'area_id');

        $stock = Stock::with([
            'product:id,status,shop_id,min_qty,max_qty,tax,img,interval,digital',
            'discount' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1)
        ])->find(data_get($data, 'stock_id', 0));

        if (!$stock?->id || $stock->product?->status !== Product::PUBLISHED) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $quantity = OrderHelper::actualQuantity($stock, data_get($data, 'quantity', 0)) ?? 0;

        data_set($data, 'quantity', $quantity);
        data_set($data, 'price', ($stock->price + $stock->tax_price) * $quantity);
        data_set($data, 'discount', $stock->actual_discount * $quantity);
        data_set($data, 'shop_id', $stock->product->shop_id);

        /** @var Cart $cart */
        $cart = $this->model()
            ->where('owner_id', $user->id)
            ->first();

        if ($cart) {
            return $this->createToExistCart($data, $cart, $user, $stock->product->shop_id);
        }

        return $this->createCart($data);
    }

    #region create when exist cart

    /**
     * @param array $data
     * @param Cart $cart
     * @param User $user
     * @param int|null $shopId
     * @return array
     */
    private function createToExistCart(array $data, Cart $cart, User $user, ?int $shopId): array
    {
        try {
            $cartId = DB::transaction(function () use ($data, $cart, $user, $shopId) {

                if (empty(data_get($data, 'name'))) {
                    $data['name'] = "$user->firstname $user->lastname";
                }

                /** @var UserCart $userCart */
                $userCart = $cart->userCarts()->firstOrCreate([
                    'cart_id' => data_get($cart, 'id'),
                    'user_id' => $user->id,
                ], $data);

                /** @var CartDetail $cartDetail */
                $cartDetail = $userCart->cartDetails()->updateOrCreate([
                    'shop_id' => $shopId,
                ]);

                /** @var CartDetailProduct $cartDetailProduct */
                $cartDetailProduct = $cartDetail->cartDetailProducts()->updateOrCreate([
                    'stock_id' => data_get($data, 'stock_id'),
                ], [
                    'quantity' => data_get($data, 'quantity', 0),
                    'price'    => data_get($data, 'price', 0),
                    'discount' => data_get($data, 'discount', 0),
                ]);

                if (!empty(data_get($data, 'images.0'))) {
                    $cartDetailProduct->galleries()->delete();
                    $cartDetailProduct->uploads(data_get($data, 'images', []));
                }

                $this->bonus($cartDetailProduct);

                return $cart->id;
            });

            return $this->successReturn($cartId);
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }
    #endregion

    #region create new cart
    /**
     * @param array $data
     * @return array
     */
    private function createCart(array $data): array
    {
        try {
            $cartId = DB::transaction(function () use ($data) {

                /** @var Cart $cart */
                $cart = $this->model()->create($data);

                if (empty(data_get($data, 'name'))) {

                    /** @var User $user */
                    $user = auth('sanctum')->user();

                    $data['name'] = "$user?->firstname $user?->lastname";
                }

                /** @var UserCart $userCarts */
                $userCarts = $cart->userCarts()->create($data);

                /** @var CartDetail $cartDetail */
                $cartDetail = $userCarts->cartDetails()->create($data);

                /** @var CartDetailProduct $cartDetailProduct */
                $cartDetailProduct  = $cartDetail->cartDetailProducts()->create($data);

                if (!empty(data_get($data, 'images.0'))) {
                    $cartDetailProduct->galleries()->delete();
                    $cartDetailProduct->uploads(data_get($data, 'images', []));
                }

                $this->bonus($cartDetailProduct);

                return $cart->id;
            });

            return $this->successReturn($cartId);
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }
    #endregion

    /**
     * @param array $data
     * @return array
     */
    public function groupCreate(array $data): array
    {
        /** @var Stock $stock */
        $stock = Stock::with([
            'product:id,status,shop_id,min_qty,max_qty,tax,img,interval',
            'discount' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1)
        ])->find(data_get($data, 'stock_id', 0));

        if (!data_get($stock, 'id') || $stock->product?->status !== Product::PUBLISHED) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
            ];
        }

        $quantity = data_get($data, 'quantity', 0);

        data_set($data, 'price', ($stock->price + $stock->tax_price) * $quantity);
        data_set($data, 'discount', $stock->actual_discount * $quantity);

        $checkQuantity = $this->checkQuantity($stock, $quantity);

        if (!data_get($checkQuantity, 'status')) {
            return $this->errorCheckQuantity($checkQuantity);
        }

        /**
         * @var Cart $model
         * @var UserCart $userCart
         */
        $model    = $this->model()->find(data_get($data, 'cart_id', 0));
        $userCart = $model->userCarts->where('uuid', data_get($data, 'user_cart_uuid'))->first();

        if (!$userCart) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => ResponseError::USER_CARTS_IS_EMPTY
            ];
        }

        try {
            $cartId = DB::transaction(function () use ($data, $model, $userCart, $stock) {

                /** @var CartDetail $cartDetail */
                $cartDetail = $userCart->cartDetails()->updateOrCreate([
                    'shop_id' => $stock->product?->shop_id,
                ]);

                /** @var CartDetailProduct $cartDetailProduct */
                $cartDetailProduct = $cartDetail->cartDetailProduct()->updateOrCreate([
                    'stock_id'      => data_get($data, 'stock_id'),
                ], [
                    'quantity'      => data_get($data, 'quantity', 0),
                    'price'         => data_get($data, 'price', 0),
                    'discount'      => data_get($data, 'discount', 0),
                ]);

                if (!empty(data_get($data, 'images.0'))) {
                    $cartDetailProduct->galleries()->delete();
                    $cartDetailProduct->uploads(data_get($data, 'images', []));
                }

                $this->bonus($cartDetailProduct);

                return $model->id;
            });

            return $this->successReturn($cartId);
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }

    }

    /**
     * @param array $data
     * @return array
     */
    public function groupInsertProducts(array $data): array
    {
        $userCart = UserCart::where('uuid', data_get($data, 'user_cart_uuid'))->first();

        $model = $userCart?->cart?->loadMissing((new CartRepository)->with());

        if (empty($model)) {
            return [
                'status'  => true,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        try {
            /** @var UserCart $userCart */
            $cartId = $this->collectProducts($data, $model, $userCart);

            return $this->successReturn($cartId);
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }

    }

    /**
     * @param array $data
     * @return array
     */
    public function openCart(array $data): array
    {
        $cart = $this->model()
            ->with('userCarts')
            ->find(data_get($data, 'cart_id', 0));

        if (empty($cart)) {
            return [
                'status'  => true,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $data['user_id'] = auth('sanctum')->id();

        /** @var Cart $cart */
        $model = $cart->userCart()->create($data);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
    }

    /**
     * @param array $data
     * @return array
     */
    public function openCartOwner(array $data): array
    {
        /** @var User $user */
        /** @var Cart $cart */
        $user  = auth('sanctum')->user();
        $model = $this->model();
        $cart  = $model->with('userCart')->where('owner_id', $user->id)->first();

        if ($cart?->userCart) {
            return $this->successReturn($cart->id);
        }

        try {
            $cartId = DB::transaction(function () use ($data, $user) {

                $cart = Cart::firstOrCreate([
                    'owner_id'   => $user->id,
                    'region_id'  => data_get($data, 'region_id'),
                    'country_id' => data_get($data, 'country_id'),
                    'city_id'    => data_get($data, 'city_id'),
                    'area_id'    => data_get($data, 'area_id'),
                ], $data);

                $cart->userCarts()
                    ->firstOrCreate([
                        'cart_id' => $cart->id,
                        'user_id' => $user->id,
                    ], $data);

                return $cart->id;
            });

            return $this->successReturn($cartId);
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }

    /**
     * @param array|null $ids
     * @return array
     */
    public function delete(?array $ids = []): array
    {
        foreach (Cart::whereIn('id', is_array($ids) ? $ids : [])->get() as $cart) {
            $cart->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    /**
     * @param $ownerId
     * @return array
     */
    public function myDelete($ownerId): array
    {
        foreach (Cart::where('owner_id', $ownerId)->get() as $cart) {
            $cart->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    /**
     * @param array|null $ids
     * @return array
     */
    public function cartProductDelete(?array $ids = []): array
    {
        /** @var CartDetailProduct $cartDetailProducts */
        $cartDetailProducts = CartDetailProduct::with([
            'stock.bonus',
            'cartDetail.userCart.cart',
        ])
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->get();

        $cart = $cartDetailProducts->first()?->cartDetail?->userCart?->cart;

        if (!$cart) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_400,
                'data'   => null,
            ];
        }

        $totalPrice = 0;

        foreach ($cartDetailProducts as $cartDetailProduct) {

            /** @var CartDetailProduct $cartDetailProduct */
            $totalPrice += ($cartDetailProduct->price - $cartDetailProduct->discount);

            if ($cartDetailProduct->stock?->bonus?->bonus_stock_id) {
                DB::table('cart_detail_products')
                    ->where('stock_id', $cartDetailProduct->stock->bonus->bonus_stock_id)
                    ->where('cart_detail_id', $cartDetailProduct->cart_detail_id)
                    ->where('bonus', true)
                    ->delete();
            }

            $cartDetailProduct->delete();
        }

        /** @var Builder $cart */
        $cart->decrement('total_price', $totalPrice);

        return $this->successReturn($cart->id);
    }

    /**
     * @param array|null $ids
     * @param int|null $cartId
     * @return array
     */
    public function userCartDelete(?array $ids = [], ?int $cartId = null): array
    {
        /** @var Cart $cart */
        $cart = $this->model()->with([
            'userCarts' => fn($q) => $q->whereIn('uuid', is_array($ids) ? $ids : []),
        ])
            ->whereHas('userCarts', fn($q) => $q->whereIn('uuid', is_array($ids) ? $ids : []))
            ->find($cartId);

        if (!$cart?->userCarts) {
            return [
                'status'  => true,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $cart->userCarts()->whereIn('uuid', is_array($ids) ? $ids : [])->delete();

        return $this->successReturn($cartId);
    }

    /**
     * @param array|null $ids
     * @param int|null $cartId
     * @return array
     */
    public function userCartDetailDelete(?array $ids = [], ?int $cartId = null): array
    {
        /** @var Cart $cart */
        $cart = $this->model()->with(['userCarts:id,cart_id'])
            ->find($cartId);

        if (!$cart) {
            return [
                'status'  => true,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        CartDetail::whereIn('user_cart_id', $cart->userCarts->pluck('id')->toArray())
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->delete();

        return $this->successReturn($cartId);
    }

    /**
     * @param string $uuid
     * @param int $cartId
     * @return array
     */
    public function statusChange(string $uuid, int $cartId): array
    {
        $cart = Cart::with([
            'userCart' => fn($query) => $query->where('uuid', $uuid)
        ])
            ->whereHas('userCart', fn($query) => $query->where('uuid', $uuid))
            ->find($cartId);

        if (empty($cart)) {
            return [
                'status'  => true,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        /** @var Cart $cart */
        $cart->userCart->update(['status' => !$cart->userCart->status]);

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => CartResource::make($cart),
        ];
    }

    /**
     * @param int|null $id
     * @return array
     */
    public function setGroup(?int $id): array
    {
        $cart = Cart::with([
            'userCart' => fn($query) => $query->where('user_id', auth('sanctum')->id())
        ])
            ->where(function ($query) {
                $query
                    ->where('owner_id', auth('sanctum')->id())
                    ->orWhereHas('userCart', fn($query) => $query->where('user_id', auth('sanctum')->id()));
            })
            ->find($id);

        if (empty($cart)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ];
        }

        /** @var Cart $cart */
        $cart->update(['group' => !$cart->group]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $cart
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    public function insertProducts(array $data): array
    {
        /** @var User $user */
        $user             = auth('sanctum')->user();
        $userId           = $user->id;
        $data['owner_id'] = $userId;
        $data['user_id']  = $userId;
        $data['rate']     = Currency::find($data['currency_id'])->rate;
        $data['city_id']  = data_get($data, 'city_id');
        $data['area_id']  = data_get($data, 'area_id');

        /** @var Cart $cart */
        $with = (new CartRepository)->with();
        $cart = $this->model()
            ->with($with)
            ->firstOrCreate([
                'owner_id'   => $userId,
                'region_id'  => data_get($data, 'region_id'),
                'country_id' => data_get($data, 'country_id'),
                'city_id'    => data_get($data, 'city_id'),
                'area_id'    => data_get($data, 'area_id'),
            ], $data);

        return $this->cartDetailsUpdate($data, $cart);
    }

    /**
     * @param array $data
     * @param Cart $cart
     * @return array
     */
    private function cartDetailsUpdate(array $data, Cart $cart): array
    {
        /** @var UserCart $userCart */
        /** @var User $user */
        $user = auth('sanctum')->user();

        $userCart = $cart->userCarts()->firstOrCreate([
            'user_id' => $user?->id,
            'cart_id' => $cart->id,
        ], [
            'uuid'    => Str::uuid(),
            'name'    => "$user?->firstname $user?->lastname"
        ]);

        $cartId = $this->collectProducts($data, $cart, $userCart);

        return $this->successReturn($cartId);
    }

    /**
     * @param array $data
     * @param Cart $cart
     * @param UserCart $userCart
     * @return int
     */
    private function collectProducts(array $data, Cart $cart, UserCart $userCart): int
    {
        try {
            return DB::transaction(function () use ($data, $cart, $userCart) {

                $cartDetailsIds = array_merge(...$cart->userCarts->pluck('cartDetails.*.id')->toArray());

                DB::table('cart_detail_products')
                    ->whereIn('cart_detail_id', $cartDetailsIds)
                    ->where('bonus', true)
                    ->delete();

                $products = collect(data_get($data, 'products', []));

                $stocks = Stock::with([
                    'product' => fn($q) => $q
                        ->select([
                            'id', 'status', 'shop_id', 'min_qty', 'max_qty', 'tax', 'interval', 'digital'
                        ])
                        ->where('status', Product::PUBLISHED),

                    'discount' => fn($q) => $q
                        ->where('start', '<=', today())
                        ->where('end', '>=', today())
                        ->where('active', 1),
                    'wholeSalePrices'
                ])
                    ->whereHas('product', fn($q) => $q->where('status', Product::PUBLISHED))
                    ->find(data_get($data, 'products.*.stock_id', []));

                $dataStocks = collect(data_get($data, 'products', []));

                foreach ($stocks as $stock) {

                    $qty = data_get($dataStocks->where('stock_id', $stock->id)->first(), 'quantity');

                    /** @var Stock $stock */
                    $product = $products->where('stock_id', $stock->id)->first();

                    $cartDetail = CartDetail::firstOrCreate([
                        'shop_id'       => $stock->product->shop_id,
                        'user_cart_id'  => $userCart->id,
                    ]);

                    if ((int)$qty === 0 || (int)data_get($product, 'quantity', 0) === 0) {

                        CartDetailProduct::where([
                            ['stock_id', $stock->id],
                            ['cart_detail_id', $cartDetail->id],
                        ])->delete();

                        continue;
                    }

                    $quantity = data_get($product, 'quantity', 0);
                    $quantity = OrderHelper::actualQuantity($stock, $quantity) ?? 0;

                    $price = $stock->price + $stock->tax_price;
                    $discount = $stock->actual_discount;

                    $wholeSalePrice = $stock->wholeSalePrices
                        ?->where('min_quantity', '<=', $quantity)
                        ?->where('max_quantity', '>=', $quantity)
                        ?->first();

                    if (!empty($wholeSalePrice)) {
                        /** @var WholeSalePrice $wholeSalePrice */
                        $price    = $wholeSalePrice->price;
                        $discount = 0;
                    }

                    $cartDetailProduct = CartDetailProduct::updateOrCreate([
                        'stock_id'       => $stock->id,
                        'cart_detail_id' => $cartDetail->id,
                    ], [
                        'quantity'       => $quantity,
                        'price'          => $price * $quantity,
                        'discount'       => $discount * $quantity,
                    ]);

                    if (!empty(data_get($product, 'images.0'))) {
                        $cartDetailProduct->galleries()->delete();
                        $cartDetailProduct->uploads(data_get($product, 'images', []));
                    }

                    $this->bonus($cartDetailProduct);
                }

                return data_get($cart, 'id');
            });
        } catch (Throwable $e) {
            $this->error($e);
            return 0;
        }
    }

    /**
     * @param CartDetailProduct $cartDetailProduct
     * @return void
     */
    public function bonus(CartDetailProduct $cartDetailProduct): void
    {
        $stock = $cartDetailProduct->stock;

        $bonusStock = Bonus::where([
            ['stock_id', $stock?->id],
            ['expired_at', '>', now()],
            ['type', Bonus::TYPE_COUNT],
        ])
            ->first();

        $bonusShop = Bonus::where([
            ['shop_id', $cartDetailProduct->cartDetail?->shop_id],
            ['type', Bonus::TYPE_SUM],
            ['expired_at', '>', now()],
        ])
            ->first();

        if ($bonusStock?->id && $bonusStock?->stock?->id) {
            $this->checkBonus($cartDetailProduct, $bonusStock);
        }

        if ($bonusShop?->id && $bonusShop?->stock?->id) {
            $this->checkBonus($cartDetailProduct, $bonusShop);
        }

    }

    /**
     * @param CartDetailProduct $cartDetailProduct
     * @param Bonus $bonus
     * @return void
     */
    private function checkBonus(CartDetailProduct $cartDetailProduct, Bonus $bonus): void
    {
        try {

            if (
                ($bonus->type === Bonus::TYPE_COUNT && $cartDetailProduct->quantity < $bonus->value)
                || empty($bonus->stock?->quantity)
                || !$bonus->status
                || !$bonus->stock?->product?->active
                || $bonus->stock?->product?->status != Product::PUBLISHED

                || empty($cartDetailProduct->stock?->quantity)
                || !$cartDetailProduct->stock?->product?->active
                || $cartDetailProduct->stock?->product?->status != Product::PUBLISHED
            ) {
                CartDetail::where([
                    'stock_id'          => $bonus->bonus_stock_id,
                    'cart_detail_id'    => $cartDetailProduct->cart_detail_id,
                    'price'             => 0,
                    'discount'          => 0,
                ])->delete();

                return;
            }

            CartDetailProduct::updateOrCreate([
                'stock_id'          => $bonus->bonus_stock_id,
                'cart_detail_id'    => $cartDetailProduct->cart_detail_id,
                'price'             => 0,
                'bonus'             => 1,
                'discount'          => 0,
            ], [
                'quantity' => $bonus->type === Bonus::TYPE_COUNT ?
                    $bonus->bonus_quantity * (int)floor($cartDetailProduct->quantity / $bonus->value) :
                    $bonus->bonus_quantity,
            ]);

        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    /**
     * @param Stock $stock
     * @param int $quantity
     * @return array
     */
    protected function checkQuantity(Stock $stock, int $quantity): array
    {
        if ($stock->quantity < $quantity) {
            return [
                'status'   => false,
                'code'     => ResponseError::NO_ERROR,
                'quantity' => $stock->quantity,
            ];
        }

        $product  = $stock->product;
        $minQty     = $product?->min_qty ?? 0;
        $maxQty     = $product?->max_qty ?? 0;

        if ($quantity < $minQty || $quantity > $maxQty) {
            return [
                'status'   => false,
                'code'     => ResponseError::NO_ERROR,
                'quantity' => "$minQty-$maxQty",
            ];
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR,];
    }

    /**
     * @param array $checkQuantity
     * @return array
     */
    private function errorCheckQuantity(array $checkQuantity): array
    {
        $data = [ 'quantity' => data_get($checkQuantity, 'quantity', 0) ];

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_111,
            'message' => __('errors.' . ResponseError::ERROR_111, $data, $this->language),
            'data'    => $data
        ];
    }

    /**
     * @param int $cartId
     * @return array
     */
    private function successReturn(int $cartId): array
    {
        /** @var Cart $cart */
        $locale = Language::where('default', 1)->first()?->locale;

        if (empty($this->language)) {
            $this->language = $locale;
        }

        $cart = $this->model()
            ->with([
                'userCarts:id,cart_id',
                'userCarts.cartDetails.cartDetailProducts.stock.product',
            ])
            ->find($cartId);

        $this->calculateTotalPrice($cart);

        $cart = Cart::with((new CartRepository)->with())->find($cart->id);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => CartResource::make($cart),
        ];
    }

    /**
     * @param Cart $cart
     * @return bool
     */
    public function calculateTotalPrice(Cart $cart): bool
    {
        if (empty($cart->userCarts)) {
            return true;
        }

        $totalPrice = 0;

        foreach ($cart->userCarts as $userCart) {

            foreach ($userCart->cartDetails as $cartDetail) {

                $price = 0;

                if ($cartDetail->cartDetailProducts?->count() === 0 && !$cart->group) {
                    $cartDetail->delete();
                    continue;
                }

                foreach ($cartDetail->cartDetailProducts as $cartDetailProduct) {

                    if (
                        empty($cartDetailProduct?->stock)
                        || $cartDetailProduct->quantity === 0
                        || !$cartDetailProduct->stock->product?->active
                        || $cartDetailProduct->stock->product?->status !== Product::PUBLISHED
                    ) {
                        $cartDetailProduct->delete();
                        continue;
                    }

                    $price += ($cartDetailProduct->price - $cartDetailProduct->discount);
                }

                $bonus = $cartDetail->shop?->bonus
                    ?->where('type',Bonus::TYPE_SUM)
                    ?->where('expired_at', '>', now())
                    ?->first();

                if ($bonus?->value > $price) {

                    $cartDetail->cartDetailProducts()
                        ->where('stock_id', $bonus->bonus_stock_id)
                        ->where('bonus', true)
                        ->delete();
                }

                $totalPrice += max($price, 0);
            }

        }

        $cart = $cart->fresh('userCarts');

        if ($cart?->userCarts?->count() === 0) {
            $totalPrice = 0;
        }

        return $cart->update(['total_price' => $totalPrice]);
    }

}

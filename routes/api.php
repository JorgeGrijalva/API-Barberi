<?php

use App\Models\Page;
use App\Http\Controllers\API\v1\{GalleryController, LikeController, PushNotificationController, Rest};
use App\Http\Controllers\API\v1\Auth\{LoginController, RegisterController, VerifyAuthController};
use App\Http\Controllers\API\v1\Dashboard\{Admin, Deliveryman, Payment, Seller, User, Master};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['prefix' => 'v1', 'middleware' => ['block.ip']], function () {
    // Methods without AuthCheck
    Route::post('/auth/register',                       [RegisterController::class, 'register'])
        ->middleware('sessions');

    Route::post('/auth/login',                          [LoginController::class, 'login'])
        ->middleware('sessions');

    Route::post('/auth/check/phone',                    [LoginController::class, 'checkPhone'])
        ->middleware('sessions');

    Route::post('/auth/logout',                         [LoginController::class, 'logout'])
        ->middleware('sessions');

    Route::post('/auth/verify/phone',                   [VerifyAuthController::class, 'verifyPhone'])
        ->middleware('sessions');

    Route::post('/auth/resend-verify',                  [VerifyAuthController::class, 'resendVerify'])
        ->middleware('sessions');

    Route::get('/auth/verify/{hash}',                   [VerifyAuthController::class, 'verifyEmail'])
        ->middleware('sessions');

    Route::post('/auth/after-verify',                   [VerifyAuthController::class, 'afterVerifyEmail'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password',                [LoginController::class, 'forgetPassword'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password/before',        [LoginController::class, 'forgetPasswordBefore'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password/confirm',        [LoginController::class, 'forgetPasswordVerify'])
        ->middleware('sessions');

    Route::post('/auth/forgot/email-password',          [LoginController::class, 'forgetPasswordEmail'])
        ->middleware('sessions');

    Route::post('/auth/forgot/email-password/{hash}',   [LoginController::class, 'forgetPasswordVerifyEmail'])
        ->middleware('sessions');

//    Route::get('/login/{provider}',                 [LoginController::class,'redirectToProvider']);
    Route::post('/auth/{provider}/callback',        [LoginController::class, 'handleProviderCallback']);

    Route::group(['prefix' => 'install'], function () {
        Route::get('/init/check',                   [Rest\InstallController::class, 'checkInitFile']);
        Route::post('/init/set',                    [Rest\InstallController::class, 'setInitFile']);
        Route::post('/database/update',             [Rest\InstallController::class, 'setDatabase']);
        Route::post('/admin/create',                [Rest\InstallController::class, 'createAdmin']);
        Route::post('/migration/run',               [Rest\InstallController::class, 'migrationRun']);
        Route::post('/check/licence',               [Rest\InstallController::class, 'licenceCredentials']);
    });

    Route::group(['prefix' => 'rest'], function () {

        /* Languages */
        Route::get('bos-ya/test',                   [Rest\TestController::class,    'bosYaTest']);
        Route::get('project/version',               [Rest\SettingController::class, 'projectVersion']);
        Route::get('timezone',                      [Rest\SettingController::class, 'timeZone']);
        Route::get('translations/paginate',         [Rest\SettingController::class, 'translationsPaginate']);
        Route::get('settings',                      [Rest\SettingController::class, 'settingsInfo']);
        Route::get('referral',                      [Rest\SettingController::class, 'referral']);
        Route::get('system/information',            [Rest\SettingController::class, 'systemInformation']);
        Route::get('stat',                          [Rest\SettingController::class, 'stat']);

        Route::get('added-review',                  [Rest\ReviewController::class, 'addedReview']);

        /* Languages */
        Route::get('languages/default',             [Rest\LanguageController::class, 'default']);
        Route::get('languages/active',              [Rest\LanguageController::class, 'active']);
        Route::get('languages/{id}',                [Rest\LanguageController::class, 'show']);
        Route::get('languages',                     [Rest\LanguageController::class, 'index']);

        /* Currencies */
        Route::get('currencies',                    [Rest\CurrencyController::class, 'index']);
        Route::get('currencies/active',             [Rest\CurrencyController::class, 'active']);

        /* CouponCheck */
        Route::get('coupons',                       [Rest\CouponController::class, 'index']);
        Route::post('coupons/check',                [Rest\CouponController::class, 'check']);
        Route::post('cashback/check',               [Rest\ProductController::class, 'checkCashback']);

        /* Products */
        Route::get('products/reviews/{uuid}',       [Rest\ProductController::class, 'reviews']);
        Route::get('order/products/calculate',      [Rest\ProductController::class, 'orderStocksCalculate']);
        Route::get('products/paginate',             [Rest\ProductController::class, 'paginate']);
        Route::get('products/ads',                  [Rest\ProductController::class, 'adsPaginate']);
        Route::get('products/brand/{id}',           [Rest\ProductController::class, 'productsByBrand']);
        Route::get('products/shop/{uuid}',          [Rest\ProductController::class, 'productsByShopUuid']);
        Route::get('products/category/{uuid}',      [Rest\ProductController::class, 'productsByCategoryUuid']);
        Route::get('products/search',               [Rest\ProductController::class, 'productsSearch']);
        Route::get('products/discount',             [Rest\ProductController::class, 'discountProducts']);
        Route::get('products/ids',                  [Rest\ProductController::class, 'productsByIDs']);
        Route::get('compare',                       [Rest\ProductController::class, 'compare']);
        Route::get('products/{uuid}',               [Rest\ProductController::class, 'show']);
        Route::get('products/slug/{slug}',          [Rest\ProductController::class, 'showSlug']);
        Route::get('products/{id}/also-bought',     [Rest\ProductController::class, 'alsoBought']);
        Route::get('products/related/{uuid}',       [Rest\ProductController::class, 'related']);
        Route::get('products/{id}/reviews-group-rating', [Rest\ProductController::class, 'reviewsGroupByRating'])
            ->where('id', '[0-9]+');

        /* Categories */
        Route::get('categories/types',              [Rest\CategoryController::class, 'types']);
        Route::get('categories/parent',             [Rest\CategoryController::class, 'parentCategory']);
        Route::get('categories/children/{id}',      [Rest\CategoryController::class, 'childrenCategory']);
        Route::get('categories/paginate',           [Rest\CategoryController::class, 'paginate']);
        Route::get('categories/select-paginate',    [Rest\CategoryController::class, 'selectPaginate']);
        Route::get('categories/search',             [Rest\CategoryController::class, 'categoriesSearch']);
        Route::get('categories/{uuid}',             [Rest\CategoryController::class, 'show']);
        Route::get('categories/slug/{slug}',        [Rest\CategoryController::class, 'showSlug']);

        /* Delivery Prices */
        Route::apiResource('delivery-prices', Rest\DeliveryPriceController::class)->only(['index', 'show']);
        Route::apiResource('delivery-points', Rest\DeliveryPointController::class)->only(['index', 'show']);

        Route::apiResource('warehouses', Rest\WarehouseController::class)->only(['index', 'show']);

        /* Brands */
        Route::get('brands/paginate',               [Rest\BrandController::class, 'paginate']);
        Route::get('brands/{id}',                   [Rest\BrandController::class, 'show']);
        Route::get('brands/slug/{slug}',            [Rest\BrandController::class, 'showSlug']);

        /* LandingPage */
        Route::get('landing-pages/paginate',        [Rest\LandingPageController::class, 'paginate']);
        Route::get('landing-pages/{type}',          [Rest\LandingPageController::class, 'show']);

        /* Shops */
        Route::get('shops/paginate',                [Rest\ShopController::class, 'paginate']);
        Route::get('shops/select-paginate',         [Rest\ShopController::class, 'selectPaginate']);
        Route::get('shops/search',                  [Rest\ShopController::class, 'shopsSearch']);
        Route::get('shops/{uuid}',                  [Rest\ShopController::class, 'show']);
        Route::get('shops/slug/{slug}',             [Rest\ShopController::class, 'showSlug']);
        Route::get('shops',                         [Rest\ShopController::class, 'shopsByIDs']);
        Route::get('shops-takes',                   [Rest\ShopController::class, 'takes']);
        Route::get('shops-statuses',                [Rest\ShopController::class, 'statuses']);
        Route::get('products-avg-prices',           [Rest\ShopController::class, 'productsAvgPrices']);
        Route::get('shops/{id}/categories',         [Rest\ShopController::class, 'categories'])->where('id', '[0-9]+');
        Route::get('shops/{id}/galleries',          [Rest\ShopController::class, 'galleries'])->where('id', '[0-9]+');
        Route::get('shops/slug/{slug}/galleries',   [Rest\ShopController::class, 'galleriesBySlug']);
        Route::get('shops/{id}/reviews',            [Rest\ShopController::class, 'reviews'])->where('id', '[0-9]+');
        Route::get('shop/{id}/memberships',         [Rest\ShopController::class, 'memberships']);
        Route::get('shop/{shopId}/membership/{id}', [Rest\ShopController::class, 'getMembershipById']);

        Route::get('shops/{id}/reviews-group-rating', [Rest\ShopController::class, 'reviewsGroupByRating'])
            ->where('id', '[0-9]+');

        /* Shop Socials */
        Route::get('shop/{id}/socials',             [Rest\ShopSocialController::class, 'socialsByShop']);

        /* Banners */
        Route::get('banners/paginate',              [Rest\BannerController::class, 'paginate']);
        Route::get('banners/{id}',                  [Rest\BannerController::class, 'show']);

        Route::apiResource('ads-packages', Rest\AdsPackageController::class)->only(['index', 'show']);
        Route::get('products-ads-packages',         [Rest\AdsPackageController::class, 'adsProducts']);

        /* FAQS */
        Route::get('faqs/paginate',                 [Rest\FAQController::class, 'paginate']);

        /* Payments */
        Route::get('payments',                      [Rest\PaymentController::class, 'index']);
        Route::get('payments/{id}',                 [Rest\PaymentController::class, 'show']);

        /* Blogs */
        Route::get('blogs/paginate',                [Rest\BlogController::class, 'paginate']);
        Route::get('blogs/{uuid}',                  [Rest\BlogController::class, 'show']);
        Route::get('blog-by-id/{id}',               [Rest\BlogController::class, 'showById']);
        Route::get('blogs/{id}/reviews',            [Rest\BlogController::class, 'reviews'])
            ->where('id', '[0-9]+');

        Route::get('blogs/{id}/reviews-group-rating', [Rest\BlogController::class, 'reviewsGroupByRating'])
            ->where('id', '[0-9]+');

        Route::get('term',                          [Rest\FAQController::class, 'term']);

        Route::get('policy',                        [Rest\FAQController::class, 'policy']);

        /* Carts */
        Route::post('cart',                         [Rest\CartController::class, 'store']);
        Route::get('cart/{id}',                     [Rest\CartController::class, 'get']);
        Route::post('cart/insert-product',          [Rest\CartController::class, 'insertProducts']);
        Route::post('cart/open',                    [Rest\CartController::class, 'openCart']);
        Route::delete('cart/product/delete',        [Rest\CartController::class, 'cartProductDelete']);
        Route::delete('cart/member/delete',         [Rest\CartController::class, 'userCartDelete']);
        Route::post('cart/status/{user_cart_uuid}', [Rest\CartController::class, 'statusChange']);

        /* Stories */
        Route::get('stories/paginate',              [Rest\StoryController::class, 'paginate']);

        /* Order Statuses */
        Route::get('order-statuses',                [Rest\OrderStatusController::class, 'index']);
        Route::get('order-statuses/select',         [Rest\OrderStatusController::class, 'select']);

        /* Tags */
        Route::get('tags/paginate',                 [Rest\TagController::class, 'paginate']);

        Route::get('shop-payments/{id}',            [Rest\ShopController::class, 'shopPayments']);

        Route::get('product-histories/paginate',    [Rest\ProductController::class, 'history']);

        Route::get('careers/paginate',              [Rest\CareerController::class, 'index']);
        Route::get('careers/{id}',                  [Rest\CareerController::class, 'show']);

        Route::get('pages/paginate',                [Rest\PageController::class, 'index']);
        Route::get('pages/{type}',                  [Rest\PageController::class, 'show'])
            ->where('type', implode('|', Page::TYPES));

        Route::apiResource('regions',   Rest\RegionController::class)->only(['index', 'show']);
        Route::get('check/countries/{id}',        [Rest\CountryController::class, 'checkCountry']);
        Route::apiResource('countries', Rest\CountryController::class)->only(['index', 'show']);
        Route::apiResource('cities',    Rest\CityController::class)->only(['index', 'show']);
        Route::apiResource('areas',     Rest\AreaController::class)->only(['index', 'show']);
        Route::get('filter',                      [Rest\FilterController::class, 'filter']);
        Route::get('shop-filter',                 [Rest\FilterController::class, 'shopFilter']);
        Route::get('search',                      [Rest\FilterController::class, 'search']);
        Route::get('search-many',                 [Rest\FilterController::class, 'searchMany']);

        /* Parcel Orders Setting */
        Route::get('parcel-order/types',            [Rest\ParcelOrderSettingController::class, 'index']);
        Route::get('parcel-order/type/{id}',        [Rest\ParcelOrderSettingController::class, 'show']);
        Route::get('parcel-order/calculate-price',  [Rest\ParcelOrderSettingController::class, 'calculatePrice']);
        Route::get('parcel-orders/get-price',       [Rest\ParcelOrderSettingController::class, 'getPrice']);

        /* Service */
        Route::apiResource('services',    Rest\ServiceController::class)->only(['index', 'show']);

        /* Service Extra */
        Route::apiResource('service-extras',    Rest\ServiceExtraController::class)->only(['index', 'show']);

        /* Master */
        Route::apiResource('masters',    Rest\MasterController::class)->only(['index', 'show']);
        Route::get('master/{id}/times',     [Rest\MasterController::class, 'times']);
        Route::get('master/times-all',      [Rest\MasterController::class, 'timesAll']);
        Route::get('master/{id}/galleries', [Rest\MasterController::class, 'galleries'])->where('id', '[0-9]+');
        Route::post('bookings/calculate',   [Rest\MasterController::class, 'calculate']);

        /* Service Master */
        Route::apiResource('service-masters', Rest\ServiceMasterController::class)->only(['index', 'show']);

        /* Master Closed Days */
        Route::apiResource('master-closed-dates', Rest\MasterClosedDateController::class)->only(['index', 'show']);

        /* Master Disabled Times */
        Route::apiResource('master-disabled-times', Rest\MasterDisabledTimeController::class)->only(['index', 'show']);

        /* Reviews */
        Route::get('users/reviews', [Rest\ReviewController::class, 'reviews'])->where('id', '[0-9]+');
        Route::get('users/{id}/reviews-group-rating', [Rest\ReviewController::class, 'reviewsGroupByRating'])->where('id', '[0-9]+');
        Route::get('review/options', [Rest\ReviewController::class, 'modelOptions']);

        /* Gift Cart */
        Route::get('gift-carts/paginate', [Rest\GiftCartController::class, 'paginate']);

        /* Form-option */
        Route::get('form-options', [Rest\FormOptionController::class, 'index']);
        Route::get('form-options/{formOption}', [Rest\FormOptionController::class, 'show']);
    });

    Route::group(['prefix' => 'payments', 'middleware' => ['sanctum.check'], 'as' => 'payment.'], function () {
        /* Transactions */
        Route::post('{type}/{id}/transactions', [Payment\TransactionController::class, 'store']);
        Route::put('{type}/{id}/transactions',  [Payment\TransactionController::class, 'updateStatus']);
    });

    Route::group(['prefix' => 'dashboard', 'middleware' => ['sanctum.check']], function () {

        /* Galleries */
        Route::get('/galleries/paginate',               [GalleryController::class, 'paginate']);
        Route::get('/galleries/types',                  [GalleryController::class, 'types']);
        Route::get('/galleries/storage/files',          [GalleryController::class, 'getStorageFiles']);
        Route::post('/galleries/storage/files/delete',  [GalleryController::class, 'deleteStorageFile']);
        Route::post('/galleries',                       [GalleryController::class, 'store']);
        Route::post('/galleries/store-many',            [GalleryController::class, 'storeMany']);

        // Likes
        Route::post('like/store-many',          [LikeController::class, 'storeMany']);
        Route::apiResource('likes',   LikeController::class);

        // Notifications
        Route::apiResource('notifications', PushNotificationController::class)->only(['index', 'show', 'destroy']);
        Route::post('notifications/{id}/read-at',   [PushNotificationController::class, 'readAt']);
        Route::post('notifications/read-all',       [PushNotificationController::class, 'readAll']);
        Route::delete('notifications/delete-all',   [PushNotificationController::class, 'deleteAll']);

        // USER BLOCK
        Route::group(['prefix' => 'user', 'middleware' => ['sanctum.check'], 'as' => 'user.'], function () {
            Route::get('chat/users/{id}',                       [User\ProfileController::class, 'chatShowById']);
            Route::get('chat-users',                            [User\ProfileController::class, 'chatUsersGet']);
            Route::get('notify/send',                           [User\ProfileController::class, 'notifySend']);
            Route::get('admin-info',                            [User\ProfileController::class, 'adminInfo']);
            Route::get('profile/show',                          [User\ProfileController::class, 'show']);
            Route::put('profile/update',                        [User\ProfileController::class, 'update']);
            Route::put('profile/lang/update',                   [User\ProfileController::class, 'langUpdate']);
            Route::put('profile/currency/update',               [User\ProfileController::class, 'currencyUpdate']);
            Route::delete('profile/delete',                     [User\ProfileController::class, 'delete']);
            Route::post('profile/firebase/token/update',        [User\ProfileController::class, 'fireBaseTokenUpdate']);
            Route::post('profile/password/update',              [User\ProfileController::class, 'passwordUpdate']);
            Route::get('profile/notifications-statistic',       [User\ProfileController::class, 'notificationStatistic']);
            Route::get('search-sending',                        [User\ProfileController::class, 'searchSending']);

            Route::get('orders/paginate',                       [User\OrderController::class, 'paginate']);
            Route::post('orders/review/{id}',                   [User\OrderController::class, 'addOrderReview']);
            Route::post('orders/deliveryman-review/{id}',       [User\OrderController::class, 'addDeliverymanReview']);
            Route::post('orders/{id}/status/change',            [User\OrderController::class, 'orderStatusChange']);
            Route::get('orders/get-active',                     [User\OrderController::class, 'getActiveOrders']);
            Route::get('orders/get-completed',                  [User\OrderController::class, 'getCompletedOrders']);
            Route::get('orders/{id}/get-all',                   [User\OrderController::class, 'ordersByParentId']);
            Route::apiResource('orders',              User\OrderController::class)->except('index');

            /* Parcel Orders */
            Route::apiResource('parcel-orders',        User\ParcelOrderController::class);
            Route::post('parcel-orders/{id}/status/change',      [User\ParcelOrderController::class, 'orderStatusChange']);
            Route::post('parcel-orders/deliveryman-review/{id}', [User\ParcelOrderController::class, 'addDeliverymanReview']);

            Route::post('address/set-active/{id}',              [User\UserAddressController::class, 'setActive']);
            Route::get('address/get-active',                    [User\UserAddressController::class, 'getActive']);
            Route::apiResource('addresses',           User\UserAddressController::class);

            Route::get('invites/paginate',                     [User\InviteController::class, 'paginate']);
            Route::post('shop/invitation/{uuid}/link',         [User\InviteController::class, 'create']);
            Route::post('shop/invitation/{id}/status/change',  [User\InviteController::class, 'changeStatus']);
            Route::delete('shop/invitation/delete',            [User\InviteController::class, 'delete']);

            Route::get('point/histories',                      [User\WalletController::class, 'pointHistories']);

            Route::get('wallet/histories',                     [User\WalletController::class, 'walletHistories']);
            Route::post('wallet/withdraw',                     [User\WalletController::class, 'store']);
            Route::post('wallet/history/{uuid}/status/change', [User\WalletController::class, 'changeStatus']);
            Route::post('wallet/send',                         [User\WalletController::class, 'send']);

            /* Transaction */
            Route::get('transactions/paginate',                 [User\TransactionController::class, 'paginate']);
            Route::get('transactions/{id}',                     [User\TransactionController::class, 'show']);

            /* User Activity */
            Route::get('user-activities',                       [User\UserActivityController::class, 'index']);
            Route::post('user-activities',                      [User\UserActivityController::class, 'storeMany']);

            /* Shop */
            Route::post('shops',                                [Seller\ShopController::class, 'shopCreate']);
            Route::get('shops',                                 [Seller\ShopController::class, 'shopShow']);
            Route::put('shops',                                 [Seller\ShopController::class, 'shopUpdate']);

            /* RequestModel */
            Route::apiResource('request-models',		User\RequestModelController::class);

            /* Ticket */
            Route::get('tickets/paginate',                      [User\TicketController::class, 'paginate']);
            Route::apiResource('tickets',             User\TicketController::class);

            /* Export */
            Route::get('export/order/{id}/pdf',                 [User\ExportController::class, 'orderExportPDF']);
            Route::get('export/all/order/{id}/pdf',             [User\ExportController::class, 'exportByParentPDF']);

            /* Carts */
            Route::post('cart',                                 [User\CartController::class, 'store']);
            Route::post('cart/insert-product',                  [User\CartController::class, 'insertProducts']);
            Route::post('cart/open',                            [User\CartController::class, 'openCart']);
            Route::post('cart/set-group/{id}',                  [User\CartController::class, 'setGroup']);
            Route::delete('cart/delete',                        [User\CartController::class, 'delete']);
            Route::delete('cart/my-delete',                     [User\CartController::class, 'myDelete']);
            Route::delete('cart/product/delete',                [User\CartController::class, 'cartProductDelete']);
            Route::delete('cart/member/delete',                 [User\CartController::class, 'userCartDelete']);
            Route::get('cart',                                  [User\CartController::class, 'get']);
            Route::post('cart/status/{user_cart_uuid}',         [User\CartController::class, 'statusChange']);
            Route::post('cart/calculate/{id}',                  [User\CartController::class, 'cartCalculate']);

            /* Order Refunds */
            Route::get('order-refunds/paginate',                [User\OrderRefundsController::class, 'paginate']);
            Route::delete('order-refunds/delete',               [User\OrderRefundsController::class, 'destroy']);
            Route::apiResource('order-refunds',               User\OrderRefundsController::class);

            Route::post('update/notifications',  [User\ProfileController::class, 'notificationsUpdate']);
            Route::get('notifications',          [User\ProfileController::class, 'notifications']);

            Route::post('stripe-process',        [Payment\StripeController::class,       'processTransaction']);
            Route::post('orange-process',        [Payment\OrangeController::class,       'processTransaction']);
            Route::post('mtn-process',           [Payment\MtnController::class,          'processTransaction']);
            Route::post('razorpay-process',      [Payment\RazorPayController::class,     'processTransaction']);
            Route::post('paystack-process',      [Payment\PayStackController::class,     'processTransaction']);
            Route::post('paytabs-process',       [Payment\PayTabsController::class,      'processTransaction']);
            Route::post('flutter-wave-process',  [Payment\FlutterWaveController::class,  'processTransaction']);
            Route::post('mercado-pago-process',  [Payment\MercadoPagoController::class,  'processTransaction']);
            Route::post('paypal-process',        [Payment\PayPalController::class,       'processTransaction']);
            Route::post('moya-sar-process',      [Payment\MoyasarController::class,      'processTransaction']);
            Route::post('mollie-process',        [Payment\MollieController::class,       'processTransaction']);
            Route::post('zain-cash-process',     [Payment\ZainCashController::class,     'processTransaction']);
            Route::post('maksekeskus-process',   [Payment\MaksekeskusController::class,  'processTransaction']);
            Route::post('iyzico-process',        [Payment\IyzicoController::class,       'processTransaction']);

            /* Digital File */
            Route::get('digital-files',         [User\DigitalFileController::class, 'index']);
            Route::get('my-digital-files',      [User\DigitalFileController::class, 'myDigitalFiles']);
            Route::get('digital-files/{id}',    [User\DigitalFileController::class, 'getDigitalFile']);

            /* Reviews */
            Route::post('products/review/{uuid}', [User\ProductController::class, 'addProductReview']);
            Route::post('shops/review/{id}',      [User\ShopController::class, 'addReviews']);
            Route::post('blogs/review/{id}',      [User\BlogController::class, 'addReviews']);
            Route::post('booking/review/{id}',    [User\BookingController::class, 'addReviews']);

            /* Bookings */
            Route::apiResource('bookings',    User\BookingController::class)->except(['destroy']);
            Route::post('bookings/{id}/status/update',  [User\BookingController::class, 'statusUpdate']);
            Route::post('bookings/{id}/notes/update',   [User\BookingController::class, 'notesUpdate']);
            Route::post('bookings/{id}/extra-time',     [User\BookingController::class, 'extraTime']);
            Route::post('booking/parent/{id}/canceled', [User\BookingController::class, 'canceledByParent']);
            Route::post('bookings/calculate',           [User\BookingController::class, 'calculate']);
            Route::get('bookings/{id}/get-all',         [User\BookingController::class, 'bookingsByParent']);

            /* Gift Cart */
            Route::get('gift-carts/my-carts',    [User\GiftCartController::class, 'myCart']);
            Route::get('gift-carts/attach/{id}', [User\GiftCartController::class, 'attach']);
            Route::post('gift-carts/send',       [User\GiftCartController::class, 'send']);

            Route::apiResource('user-memberships', User\MemberShipController::class)->only(['index', 'show']);
            Route::delete('user-memberships/delete', [User\MemberShipController::class, 'destroy']);
        });

        // MASTER BLOCK
        Route::group(['prefix' => 'master', 'middleware' => ['sanctum.check', 'role:master'], 'as' => 'master.'], function () {

            /* Service */
            Route::apiResource('services', Master\ServiceController::class)->only(['index', 'show']);

            /* Service Master */
            Route::apiResource('service-masters', Master\ServiceMasterController::class);
            Route::delete('service-masters/delete', [Master\ServiceMasterController::class, 'destroy']);

            /* Service Master Notifications */
            Route::apiResource('service-master-notifications', Master\ServiceMasterNotificationController::class);
            Route::delete('service-master-notifications/delete', [Master\ServiceMasterNotificationController::class, 'destroy']);

            /* Master Closed Days */
            Route::apiResource('master-closed-dates', Master\MasterClosedDateController::class);
            Route::delete('master-closed-dates/delete', [Master\MasterClosedDateController::class, 'destroy']);

            /* Master Disabled Times */
            Route::apiResource('master-disabled-times', Master\MasterDisabledTimeController::class);
            Route::delete('master-disabled-times/delete', [Master\MasterDisabledTimeController::class, 'destroy']);

            /* Bookings */
            Route::apiResource('bookings',    Master\BookingController::class);
            Route::get('bookings/{id}/get-all',         [Master\BookingController::class, 'bookingsByParent']);
            Route::post('bookings/{id}/status/update',  [Master\BookingController::class, 'statusUpdate']);
            Route::post('bookings/{id}/notes/update',   [Master\BookingController::class, 'notesUpdate']);
            Route::post('bookings/{id}/times/update',   [Master\BookingController::class, 'timesUpdate']);
            Route::post('bookings/{id}/extra-time',     [Master\BookingController::class, 'extraTime']);
            Route::post('bookings/calculate',           [Master\BookingController::class, 'calculate']);
            Route::delete('bookings/delete',            [Master\BookingController::class, 'destroy']);

            /* Bookings report */
            Route::apiResource('users',       Master\UserController::class)->only(['index', 'show']);
            Route::get('booking/reports/statistic',     [Master\BookingReportController::class, 'statistic']);

            Route::get('galleries',  [Master\GalleryController::class, 'index']);
            Route::post('galleries', [Master\GalleryController::class, 'store']);

            /* User Working Days */
            Route::apiResource('user-working-days', Master\UserWorkingDayController::class);
            Route::delete('user-working-days/delete', [Master\UserWorkingDayController::class, 'destroy']);

            /* Form-option */
            Route::apiResource('form-options', Master\FormOptionController::class);
            Route::delete('form-options/delete', [Master\FormOptionController::class, 'destroy']);

        });

        // DELIVERYMAN BLOCK
        Route::group(['prefix' => 'deliveryman', 'middleware' => ['sanctum.check', 'role:deliveryman'], 'as' => 'deliveryman.'], function () {
            Route::get('orders/paginate',           [Deliveryman\OrderController::class, 'paginate']);
            Route::get('orders/{id}',               [Deliveryman\OrderController::class, 'show']);
            Route::post('order/{id}/status/update', [Deliveryman\OrderController::class, 'orderStatusUpdate']);
            Route::post('orders/{id}/review',       [Deliveryman\OrderController::class, 'addReviewByDeliveryman']);
            Route::post('orders/{id}/current',      [Deliveryman\OrderController::class, 'setCurrent']);
            Route::get('statistics/count',          [Deliveryman\DashboardController::class, 'countStatistics']);

            Route::post('settings',                 [Deliveryman\DeliveryManSettingController::class, 'store']);
            Route::post('settings/location',        [Deliveryman\DeliveryManSettingController::class, 'updateLocation']);
            Route::post('settings/online',          [Deliveryman\DeliveryManSettingController::class, 'online']);
            Route::get('settings',                  [Deliveryman\DeliveryManSettingController::class, 'show']);
            Route::post('order/{id}/attach/me',     [Deliveryman\OrderController::class, 'orderDeliverymanUpdate']);

            /* Payouts */
            Route::apiResource('payouts', Deliveryman\PayoutsController::class);

            Route::delete('payouts/delete', [Deliveryman\PayoutsController::class, 'destroy']);

            /* Report Orders */
            Route::get('order/report', [Deliveryman\OrderReportController::class, 'report']);

            Route::get('parcel-orders/paginate',            [Deliveryman\ParcelOrderController::class, 'paginate']);
            Route::post('parcel-orders/{id}/status/update', [Deliveryman\ParcelOrderController::class, 'orderStatusUpdate']);
            Route::post('parcel-orders/{id}/review',        [Deliveryman\ParcelOrderController::class, 'addReviewByDeliveryman']);
            Route::post('parcel-order/{id}/current',        [Deliveryman\ParcelOrderController::class, 'setCurrent']);
            Route::post('parcel-order/{id}/attach/me',      [Deliveryman\ParcelOrderController::class, 'orderDeliverymanUpdate']);

            Route::apiResource('payment-to-partners', Deliveryman\PaymentToPartnerController::class)
                ->only(['index', 'show']);
        });

        // SELLER BLOCK
        Route::group(['prefix' => 'seller', 'middleware' => ['sanctum.check', 'role:seller|moderator|admin'], 'as' => 'seller.'], function () {

            /* Dashboard */
            Route::get('statistics',                [Seller\DashboardController::class, 'ordersStatistics']);
            Route::get('statistics/orders/chart',   [Seller\DashboardController::class, 'ordersChart']);
            Route::get('statistics/products',       [Seller\DashboardController::class, 'productsStatistic']);
            Route::get('statistics/users',          [Seller\DashboardController::class, 'usersStatistic']);
            Route::get('statistics/sales',          [Seller\DashboardController::class, 'salesReport']);

            Route::get('sales-history',             [Seller\Report\Sales\HistoryController::class, 'history']);
            Route::get('sales-cards',               [Seller\Report\Sales\HistoryController::class, 'cards']);
            Route::get('sales-main-cards',          [Seller\Report\Sales\HistoryController::class, 'mainCards']);
            Route::get('sales-chart',               [Seller\Report\Sales\HistoryController::class, 'chart']);
            Route::get('sales-statistic',           [Seller\Report\Sales\HistoryController::class, 'statistic']);

            Route::group(['prefix' => 'booking/reports'], function () {
                Route::get('payments',  [Seller\Report\Booking\PaymentTypeController::class, 'payments']);
                Route::get('summary',   [Seller\Report\Booking\PaymentTypeController::class, 'summary']);
                Route::get('cards',     [Seller\Report\Booking\PaymentTypeController::class, 'cards']);
                Route::get('chart',     [Seller\Report\Booking\PaymentTypeController::class, 'chart']);
                Route::get('masters',   [Seller\Report\Booking\PaymentTypeController::class, 'masters']);
                Route::get('statistic', [Seller\Report\Booking\BookingReportController::class, 'statistic']);
            });

            /* Extras Group & Value */
            Route::get('extra/groups/types',        [Seller\ExtraGroupController::class, 'typesList']);

            Route::apiResource('extra/groups', Seller\ExtraGroupController::class);
            Route::delete('extra/groups/delete',    [Seller\ExtraGroupController::class, 'destroy']);

            Route::apiResource('extra/values', Seller\ExtraValueController::class);
            Route::delete('extra/values/delete',    [Seller\ExtraValueController::class, 'destroy']);

            /* Property Group & Value */
            Route::get('property/groups/types',     [Seller\PropertyGroupController::class, 'typeList']);

            Route::apiResource('property/groups', Seller\PropertyGroupController::class);
            Route::delete('property/groups/delete', [Seller\PropertyGroupController::class, 'destroy']);

            Route::apiResource('property/values', Seller\PropertyValueController::class);
            Route::delete('property/values/delete', [Seller\PropertyValueController::class, 'destroy']);

            /* Units */
            Route::get('units/paginate',            [Seller\UnitController::class, 'paginate']);
            Route::get('units/{id}',                [Seller\UnitController::class, 'show']);

            /* Seller Shop */
            Route::get('shops',                     [Seller\ShopController::class, 'shopShow']);
            Route::put('shops',                     [Seller\ShopController::class, 'shopUpdate']);
            Route::post('shops/working/status',     [Seller\ShopController::class, 'setWorkingStatus']);

            /* Shop Socials */
            Route::apiResource('shop-socials',Seller\ShopSocialController::class);
            Route::delete('shop-socials/delete',    [Seller\ShopSocialController::class, 'destroy']);

            /* Shop Locations */
            Route::apiResource('shop-locations', Seller\ShopLocationController::class);
            Route::delete('shop-locations/delete',  [Seller\ShopLocationController::class, 'destroy']);

            /* Categories */
            Route::get('categories/export',                 [Seller\CategoryController::class, 'fileExport']);
            Route::post('categories/{uuid}/image/delete',   [Seller\CategoryController::class, 'imageDelete']);
            Route::get('categories/search',                 [Seller\CategoryController::class, 'categoriesSearch']);
            Route::get('categories/paginate',               [Seller\CategoryController::class, 'paginate']);
            Route::get('categories/select-paginate',        [Seller\CategoryController::class, 'selectPaginate']);
            Route::get('my-categories/select-paginate',     [Seller\CategoryController::class, 'mySelectPaginate']);
            Route::post('categories/import',                [Seller\CategoryController::class, 'fileImport']);
            Route::apiResource('categories',       Seller\CategoryController::class);
            Route::delete('categories/delete',              [Seller\CategoryController::class, 'destroy']);
            Route::post('categories/{uuid}/active',         [Seller\CategoryController::class, 'changeActive']);

            Route::get('brands/export',                     [Seller\BrandController::class, 'fileExport']);
            Route::post('brands/import',                    [Seller\BrandController::class, 'fileImport']);
            Route::get('brands/paginate',                   [Seller\BrandController::class, 'paginate']);
            Route::get('brands/search',                     [Seller\BrandController::class, 'brandsSearch']);
            Route::apiResource('brands',           Seller\BrandController::class);

            /* Seller Product */
            Route::post('products/parent/sync',         [Seller\ProductController::class, 'parentSync']);
            Route::post('products/import',              [Seller\ProductController::class, 'fileImport']);
            Route::get('products/export',               [Seller\ProductController::class, 'fileExport']);
            Route::get('products/paginate',             [Seller\ProductController::class, 'paginate']);
            Route::get('products/search',               [Seller\ProductController::class, 'productsSearch']);
            Route::post('products/{uuid}/properties',   [Seller\ProductController::class, 'addProductProperties']);
            Route::post('products/{uuid}/stocks',       [Seller\ProductController::class, 'addInStock']);
            Route::post('stocks/galleries',             [Seller\ProductController::class, 'stockGalleryUpdate']);
            Route::post('products/{uuid}/active',       [Seller\ProductController::class, 'setActive']);
            Route::get('stocks/select-paginate',        [Seller\ProductController::class, 'selectStockPaginate']);
            Route::delete('products/delete',            [Seller\ProductController::class, 'destroy']);
            Route::apiResource('products',    Seller\ProductController::class);

            Route::get('filter',                        [Seller\FilterController::class, 'filter']);

            /* Seller Shop Users */
            Route::get('shop/users/paginate',           [Seller\UserController::class, 'shopUsersPaginate']);
            Route::get('shop/users/role/deliveryman',   [Seller\UserController::class, 'getDeliveryman']);
            Route::get('shop/users/{uuid}',             [Seller\UserController::class, 'shopUserShow']);

            /* Seller Users */
            Route::get('users/paginate',                [Seller\UserController::class, 'paginate']);
            Route::get('users/{uuid}',                  [Seller\UserController::class, 'show']);
            Route::post('users/{uuid}/change/status',   [Seller\UserController::class, 'setUserActive']);
            Route::apiResource('users',       Seller\UserController::class)->only(['store', 'update']);

            /* User Working Days */
            Route::apiResource('user-working-days', Seller\UserWorkingDayController::class);
            Route::delete('user-working-days/delete',   [Seller\UserWorkingDayController::class, 'destroy']);

            /* Seller Invite */
            Route::get('shops/invites/paginate',             [Seller\InviteController::class, 'paginate']);
            Route::post('shops/invites/{id}/status/change',  [Seller\InviteController::class, 'changeStatus']);
            Route::post('shop/invitation/link',              [Seller\InviteController::class, 'create']);
            Route::delete('shop/invitations/delete',         [Seller\InviteController::class, 'delete']);

            /* Seller discount */
            Route::get('discounts/paginate',            [Seller\DiscountController::class, 'paginate']);
            Route::post('discounts/{id}/active/status', [Seller\DiscountController::class, 'setActiveStatus']);
            Route::delete('discounts/delete',           [Seller\DiscountController::class, 'destroy']);
            Route::apiResource('discounts',   Seller\DiscountController::class)->except('index');

            /* Seller Banner */
            Route::get('banners/paginate',          [Seller\BannerController::class, 'paginate']);
            Route::post('banners/active/{id}',      [Seller\BannerController::class, 'setActiveBanner']);
            Route::delete('banners/delete',         [Seller\BannerController::class, 'destroy']);
            Route::apiResource('banners', Seller\BannerController::class);

            /* Seller Order */
            Route::get('order/export',                [Seller\OrderController::class, 'fileExport']);
            Route::post('order/import',               [Seller\OrderController::class, 'fileImport']);
            Route::get('order/products/calculate',    [Seller\OrderController::class, 'orderStocksCalculate']);
            Route::get('orders/paginate',             [Seller\OrderController::class, 'paginate']);
            Route::post('order/{id}/status',          [Seller\OrderController::class, 'orderStatusUpdate']);
            Route::post('order/{id}/tracking',        [Seller\OrderController::class, 'orderTrackingUpdate']);
            Route::apiResource('orders',    Seller\OrderController::class)->except('index');
            Route::delete('orders/delete',            [Seller\OrderController::class, 'destroy']);

            /* Transaction */
            Route::get('transactions/paginate', [Seller\TransactionController::class, 'paginate']);
            Route::get('transactions/{id}',     [Seller\TransactionController::class, 'show']);
            Route::post('transactions/{id}',    [Seller\TransactionController::class, 'update']);

            /* Seller Subscription */
            Route::get('subscriptions',               [Seller\SubscriptionController::class, 'index']);
            Route::get('my-subscriptions',            [Seller\SubscriptionController::class, 'mySubscription']);
            Route::post('subscriptions/{id}/attach',  [Seller\SubscriptionController::class, 'subscriptionAttach']);

            /* OnResponse Shop */
            Route::apiResource('bonuses',     Seller\BonusController::class);
            Route::post('bonuses/status/{id}',  [Seller\BonusController::class, 'statusChange']);
            Route::delete('bonuses/delete',     [Seller\BonusController::class, 'destroy']);

            /* Stories */
            Route::post('stories/upload',       [Seller\StoryController::class, 'uploadFiles']);

            Route::apiResource('stories',     Seller\StoryController::class);
            Route::delete('stories/delete',     [Seller\StoryController::class, 'destroy']);

            /* Tags */
            Route::apiResource('tags',        Seller\TagController::class);
            Route::delete('tags/delete',        [Seller\TagController::class, 'destroy']);
            Route::get('shop-tags/paginate',    [Seller\TagController::class, 'shopTagsPaginate']);

            /* Payments */
            Route::post('shop-payments/{id}/active/status', [Seller\ShopPaymentController::class, 'setActive']);
            Route::get('shop-payments/shop-non-exist',      [Seller\ShopPaymentController::class, 'shopNonExist']);
            Route::get('shop-payments/delete',              [Seller\ShopPaymentController::class, 'destroy']);
            Route::apiResource('shop-payments',   Seller\ShopPaymentController::class);

            /* Order Refunds */
            Route::get('order-refunds/paginate',  [Seller\OrderRefundsController::class, 'paginate']);
            Route::delete('order-refunds/delete', [Seller\OrderRefundsController::class, 'destroy']);
            Route::apiResource('order-refunds', Seller\OrderRefundsController::class);

            /* Shop Working Days */
            Route::apiResource('shop-working-days', Seller\ShopWorkingDayController::class)
                ->except('store');
            Route::delete('shop-working-days/delete', [Seller\ShopWorkingDayController::class, 'destroy']);

            /* Shop Closed Days */
            Route::apiResource('shop-closed-dates', Seller\ShopClosedDateController::class)
                ->except('store');
            Route::delete('shop-closed-dates/delete', [Seller\ShopClosedDateController::class, 'destroy']);

            /* Payouts */
            Route::apiResource('payouts', Seller\PayoutsController::class);

            Route::delete('payouts/delete', [Seller\PayoutsController::class, 'destroy']);

            /* Report Orders */
            Route::get('orders/report/chart',    [Seller\OrderReportController::class, 'reportChart']);
            Route::get('orders/report/paginate', [Seller\OrderReportController::class, 'reportChartPaginate']);
            Route::get('orders/report/transactions', [Seller\OrderReportController::class, 'reportTransactions']);

            /* Reviews */
            Route::get('reviews/paginate',          [Seller\ReviewController::class, 'paginate']);
            Route::apiResource('reviews', Seller\ReviewController::class)->only('show');

            /* Galleries */
            Route::apiResource('galleries',Seller\ShopGalleriesController::class)->except('show');

            /* Shop Deliveryman Setting */
            Route::apiResource('shop-deliveryman-settings',Seller\ShopDeliverymanSettingController::class);
            Route::delete('shop-deliveryman-settings/delete',        [Seller\ShopDeliverymanSettingController::class, 'destroy']);

            /* Digital File */
            Route::apiResource('digital-files',   Seller\DigitalFileController::class);
            Route::get('digital-file/{id}/active', [Seller\DigitalFileController::class, 'changeActive']);
            Route::delete('digital-files/delete',  [Seller\DigitalFileController::class, 'destroy']);

            /* AdsPackage */
            Route::apiResource('ads-packages', Seller\AdsPackageController::class)
                ->only(['index', 'show']);

            Route::apiResource('shop-ads-packages', Seller\ShopAdsPackageController::class);

            /* RequestModel */
            Route::apiResource('request-models', Seller\RequestModelController::class);
            Route::delete('request-models/delete',        [Seller\RequestModelController::class, 'destroy']);

            Route::apiResource('payment-to-partners', Seller\PaymentToPartnerController::class)
                ->only(['index', 'show']);

            /* Service */
            Route::apiResource('services',    Seller\ServiceController::class);
            Route::post('services/{id}/extras',   [Seller\ServiceController::class, 'extrasUpdate']);
            Route::post('services/{id}/faqs',     [Seller\ServiceController::class, 'faqsUpdate']);
            Route::delete('services/delete',    [Seller\ServiceController::class, 'destroy']);

            /* Service FAQ */
            Route::post('service-faqs/{id}/active/status',  [Seller\ServiceFaqController::class, 'setActiveStatus']);
            Route::apiResource('service-faqs',          Seller\ServiceFaqController::class);
            Route::delete('service-faqs/delete',            [Seller\ServiceController::class, 'destroy']);

            Route::get('master/{id}/times',     [Seller\UserController::class, 'times']);

            /* Service Master */
            Route::apiResource('service-masters', Seller\ServiceMasterController::class);
            Route::delete('service-masters/delete', [Seller\ServiceMasterController::class, 'destroy']);

            /* Service Master Notifications */
            Route::apiResource('service-master-notifications', Seller\ServiceMasterNotificationController::class);
            Route::delete('service-master-notifications/delete', [Seller\ServiceMasterNotificationController::class, 'destroy']);

            /* Master Disabled Times */
            Route::apiResource('master-closed-dates', Seller\MasterClosedDateController::class);
            Route::delete('master-closed-dates/delete', [Seller\MasterClosedDateController::class, 'destroy']);

            /* Master Disabled Times */
            Route::apiResource('master-disabled-times', Seller\MasterDisabledTimeController::class);
            Route::delete('master-disabled-times/delete', [Seller\MasterDisabledTimeController::class, 'destroy']);

            /* Bookings */
            Route::apiResource('bookings',   Seller\BookingController::class);
            Route::get('bookings/{id}/get-all',        [Seller\BookingController::class, 'bookingsByParent']);
            Route::post('bookings/{id}/status/update', [Seller\BookingController::class, 'statusUpdate']);
            Route::post('bookings/{id}/notes/update',  [Seller\BookingController::class, 'notesUpdate']);
            Route::post('bookings/{id}/times/update',  [Seller\BookingController::class, 'timesUpdate']);
            Route::post('bookings/{id}/extra-time',    [Seller\BookingController::class, 'extraTime']);
            Route::delete('bookings/delete',           [Seller\BookingController::class, 'destroy']);
            Route::post('bookings/calculate',          [Seller\BookingController::class, 'calculate']);

            /* Coupon */
            Route::get('coupons/paginate',  [Seller\CouponController::class, 'paginate']);
            Route::delete('coupons/delete', [Seller\CouponController::class, 'destroy']);
            Route::apiResource('coupons', Seller\CouponController::class);

            /* Delivery prices */
            Route::apiResource('delivery-prices', Seller\DeliveryPriceController::class);
            Route::delete('delivery-prices/delete', [Seller\DeliveryPriceController::class, 'destroy']);
            Route::get('delivery-prices/drop/all',  [Seller\DeliveryPriceController::class, 'dropAll']);

            /* GiftCart */
            Route::apiResource('gift-carts', Seller\GiftCartController::class);
            Route::delete('gift-carts/delete', [Seller\GiftCartController::class, 'destroy']);

            /* UserGiftCart */
            Route::apiResource('user-gift-carts', Seller\UserGiftCartController::class)->only(['index', 'show']);

            /* Memberships */
            Route::apiResource('memberships', Seller\MemberShipController::class);
            Route::delete('memberships/delete', [Seller\MemberShipController::class, 'destroy']);

            /*User Membership */
            Route::apiResource('user-memberships', Seller\UserMemberShipController::class)->only(['index', 'show']);

            /* Service Extra */
            Route::apiResource('service-extras', Seller\ServiceExtraController::class)->except(['destroy']);
            Route::delete('service-extras/delete', [Seller\ServiceExtraController::class, 'destroy']);
            Route::get('service-extras/drop/all',  [Seller\ServiceExtraController::class, 'dropAll']);

            /* Form-option */
            Route::apiResource('form-options', Seller\FormOptionController::class);
            Route::delete('form-options/delete', [Seller\FormOptionController::class, 'destroy']);

            Route::apiResource('deliveryman-settings', Seller\DeliveryManSettingController::class);
            Route::delete('deliveryman-settings/delete',  [Seller\DeliveryManSettingController::class, 'destroy']);

            /* User address */
            Route::apiResource('user-addresses', Seller\UserAddressController::class)->only(['index', 'store', 'show']);
        });

        // ADMIN BLOCK
        Route::group(['prefix' => 'admin', 'middleware' => ['sanctum.check', 'role:seller|manager'], 'as' => 'admin.'], function () {

            /* Dashboard */
            Route::get('timezones',                 [Admin\DashboardController::class, 'timeZones']);
            Route::get('timezone',                  [Admin\DashboardController::class, 'timeZone']);
            Route::post('timezone',                 [Admin\DashboardController::class, 'timeZoneChange']);

            Route::get('statistics',                [Admin\DashboardController::class, 'ordersStatistics']);
            Route::get('statistics/orders/chart',   [Admin\DashboardController::class, 'ordersChart']);
            Route::get('statistics/products',       [Admin\DashboardController::class, 'productsStatistic']);
            Route::get('statistics/users',          [Admin\DashboardController::class, 'usersStatistic']);
            Route::get('statistics/sales',          [Admin\DashboardController::class, 'salesReport']);

            /* Terms & Condition */
            Route::post('term',                     [Admin\TermsController::class, 'store']);
            Route::get('term',                      [Admin\TermsController::class, 'show']);
            Route::get('term/drop/all',             [Admin\TermsController::class, 'dropAll']);

            /* Privacy & Policy */
            Route::post('policy',                   [Admin\PrivacyPolicyController::class, 'store']);
            Route::get('policy',                    [Admin\PrivacyPolicyController::class, 'show']);
            Route::get('policy/drop/all',           [Admin\PrivacyPolicyController::class, 'dropAll']);

            /* Reviews */
            Route::get('reviews/paginate',          [Admin\ReviewController::class, 'paginate']);
            Route::apiResource('reviews', Admin\ReviewController::class);
            Route::delete('reviews/delete',         [Admin\ReviewController::class, 'destroy']);
            Route::get('reviews/drop/all',          [Admin\ReviewController::class, 'dropAll']);

            /* Galleries */
            Route::apiResource('galleries',Admin\ShopGalleriesController::class);

            /* Languages */
            Route::get('languages/default',             [Admin\LanguageController::class, 'getDefaultLanguage']);
            Route::post('languages/default/{id}',       [Admin\LanguageController::class, 'setDefaultLanguage']);
            Route::get('languages/active',              [Admin\LanguageController::class, 'getActiveLanguages']);
            Route::post('languages/{id}/image/delete',  [Admin\LanguageController::class, 'imageDelete']);
            Route::apiResource('languages',   Admin\LanguageController::class);
            Route::delete('languages/delete',           [Admin\LanguageController::class, 'destroy']);
            Route::get('languages/drop/all',            [Admin\LanguageController::class, 'dropAll']);

            /* Currencies */
            Route::get('currencies/default',            [Admin\CurrencyController::class, 'getDefaultCurrency']);
            Route::post('currencies/default/{id}',      [Admin\CurrencyController::class, 'setDefaultCurrency']);
            Route::get('currencies/active',             [Admin\CurrencyController::class, 'getActiveCurrencies']);
            Route::apiResource('currencies',  Admin\CurrencyController::class);
            Route::delete('currencies/delete',          [Admin\CurrencyController::class, 'destroy']);
            Route::get('currencies/drop/all',           [Admin\CurrencyController::class, 'dropAll']);

            /* Categories */
            Route::get('categories/export',                 [Admin\CategoryController::class, 'fileExport']);
            Route::post('categories/{uuid}/image/delete',   [Admin\CategoryController::class, 'imageDelete']);
            Route::get('categories/search',                 [Admin\CategoryController::class, 'categoriesSearch']);
            Route::get('categories/paginate',               [Admin\CategoryController::class, 'paginate']);
            Route::get('categories/select-paginate',        [Admin\CategoryController::class, 'selectPaginate']);
            Route::post('categories/import',                [Admin\CategoryController::class, 'fileImport']);
            Route::apiResource('categories',      Admin\CategoryController::class);
            Route::post('category-input/{uuid}',            [Admin\CategoryController::class, 'changeInput']);
            Route::post('categories/{uuid}/active',         [Admin\CategoryController::class, 'changeActive']);
            Route::post('categories/{uuid}/status',         [Admin\CategoryController::class, 'changeStatus']);
            Route::delete('categories/delete',              [Admin\CategoryController::class, 'destroy']);
            Route::get('categories/drop/all',               [Admin\CategoryController::class, 'dropAll']);

            /* Brands */
            Route::get('brands/export',             [Admin\BrandController::class, 'fileExport']);
            Route::post('brands/import',            [Admin\BrandController::class, 'fileImport']);
            Route::get('brands/paginate',           [Admin\BrandController::class, 'paginate']);
            Route::get('brands/search',             [Admin\BrandController::class, 'brandsSearch']);
            Route::apiResource('brands',  Admin\BrandController::class);
            Route::delete('brands/delete',          [Admin\BrandController::class, 'destroy']);
            Route::get('brands/drop/all',           [Admin\BrandController::class, 'dropAll']);

            /* Banner */
            Route::get('banners/paginate',          [Admin\BannerController::class, 'paginate']);
            Route::post('banners/active/{id}',      [Admin\BannerController::class, 'setActiveBanner']);
            Route::apiResource('banners', Admin\BannerController::class);
            Route::delete('banners/delete',         [Admin\BannerController::class, 'destroy']);
            Route::get('banners/drop/all',          [Admin\BannerController::class, 'dropAll']);

            /* LandingPage */
            Route::apiResource('landing-pages',  Admin\LandingPageController::class);
            Route::delete('landing-pages/delete',   [Admin\LandingPageController::class, 'destroy']);
            Route::get('landing-pages/drop/all',    [Admin\LandingPageController::class, 'dropAll']);

            /* Units */
            Route::get('units/paginate',            [Admin\UnitController::class, 'paginate']);
            Route::post('units/active/{id}',        [Admin\UnitController::class, 'setActiveUnit']);
            Route::delete('units/delete',           [Admin\UnitController::class, 'destroy']);
            Route::get('units/drop/all',            [Admin\UnitController::class, 'dropAll']);
            Route::apiResource('units',  Admin\UnitController::class)->except('destroy');

            /* Shops */
            Route::get('shop/export',                   [Admin\ShopController::class, 'fileExport']);
            Route::post('shop/import',                  [Admin\ShopController::class, 'fileImport']);
            Route::get('shops/search',                  [Admin\ShopController::class, 'shopsSearch']);
            Route::get('shops/paginate',                [Admin\ShopController::class, 'paginate']);
            Route::post('shops/{uuid}/image/delete',    [Admin\ShopController::class, 'imageDelete']);
            Route::post('shops/{uuid}/status/change',   [Admin\ShopController::class, 'statusChange']);
            Route::apiResource('shops',       Admin\ShopController::class);
            Route::delete('shops/delete',               [Admin\ShopController::class, 'destroy']);
            Route::get('shops/drop/all',                [Admin\ShopController::class, 'dropAll']);
            Route::post('shops/working/status',         [Admin\ShopController::class, 'setWorkingStatus']);
            Route::post('shops/{uuid}/verify',          [Admin\ShopController::class, 'setVerify']);

            /* Shop Socials*/
            Route::apiResource('shop-socials',Admin\ShopSocialController::class);
            Route::delete('shop-socials/delete',        [Admin\ShopSocialController::class, 'destroy']);
            Route::get('shop-socials/drop/all',         [Admin\ShopSocialController::class, 'dropAll']);

            /* Shop Locations */
            Route::apiResource('shop-locations', Admin\ShopLocationController::class);
            Route::delete('shop-locations/delete',          [Admin\ShopLocationController::class, 'destroy']);
            Route::get('shop-locations/drop/all',           [Admin\ShopLocationController::class, 'dropAll']);

            /* Extras Group & Value */
            Route::get('extra/groups/types',            [Admin\ExtraGroupController::class, 'typesList']);

            Route::apiResource('extra/groups', Admin\ExtraGroupController::class);
            Route::delete('extra/groups/delete',        [Admin\ExtraGroupController::class, 'destroy']);
            Route::get('extra/groups/drop/all',         [Admin\ExtraGroupController::class, 'dropAll']);

            Route::apiResource('extra/values', Admin\ExtraValueController::class);
            Route::delete('extra/values/delete',        [Admin\ExtraValueController::class, 'destroy']);
            Route::get('extra/values/drop/all',         [Admin\ExtraValueController::class, 'dropAll']);

            /* Property Group & Value */
            Route::get('property/groups/types',         [Admin\PropertyGroupController::class, 'typeList']);
            Route::apiResource('property/groups', Admin\PropertyGroupController::class);
            Route::delete('property/groups/delete',     [Admin\PropertyGroupController::class, 'destroy']);
            Route::post('property/groups/{id}/active',  [Admin\PropertyGroupController::class, 'changeActive']);

            Route::apiResource('property/values', Admin\PropertyValueController::class);
            Route::delete('property/values/delete',     [Admin\PropertyValueController::class, 'destroy']);
            Route::post('property/values/{id}/active',  [Admin\PropertyValueController::class, 'changeActive']);

            /* Products */
            Route::get('products/export',                [Admin\ProductController::class, 'fileExport']);
            Route::get('most-popular/products',          [Admin\ProductController::class, 'mostPopulars']);
            Route::post('products/import',               [Admin\ProductController::class, 'fileImport']);
            Route::get('products/paginate',              [Admin\ProductController::class, 'paginate']);
            Route::get('products/search',                [Admin\ProductController::class, 'productsSearch']);
            Route::post('products/{uuid}/properties',    [Admin\ProductController::class, 'addProductProperties']);
            Route::post('products/{uuid}/stocks',        [Admin\ProductController::class, 'addInStock']);
            Route::post('stocks/galleries',              [Admin\ProductController::class, 'stockGalleryUpdate']);
            Route::post('products/{uuid}/active',        [Admin\ProductController::class, 'setActive']);
            Route::post('products/{uuid}/status/change', [Admin\ProductController::class, 'setStatus']);
            Route::apiResource('products',     Admin\ProductController::class);
            Route::delete('products/delete',             [Admin\ProductController::class, 'destroy']);
            Route::get('products/drop/all',              [Admin\ProductController::class, 'dropAll']);
            Route::get('stocks/drop/all',                [Admin\ProductController::class, 'dropAllStocks']);
            Route::get('stocks/select-paginate',         [Admin\ProductController::class, 'selectStockPaginate']);

            /* Orders */
            Route::get('order/export',                   [Admin\OrderController::class, 'fileExport']);
            Route::post('order/import',                  [Admin\OrderController::class, 'fileImport']);
            Route::get('orders/paginate',                [Admin\OrderController::class, 'paginate']);
            Route::get('order/products/calculate',       [Admin\OrderReportController::class, 'orderStocksCalculate']);
            Route::post('order/{id}/deliveryman',        [Admin\OrderController::class, 'orderDeliverymanUpdate']);
            Route::post('order/{id}/status',             [Admin\OrderController::class, 'orderStatusUpdate']);
            Route::apiResource('orders',       Admin\OrderController::class);
            Route::delete('orders/delete',               [Admin\OrderController::class, 'destroy']);
            Route::get('orders/drop/all',                [Admin\OrderController::class, 'dropAll']);
            Route::get('user-orders/{id}',               [Admin\OrderController::class, 'userOrder']);
            Route::get('user-orders/{id}/paginate',      [Admin\OrderController::class, 'userOrders']);
            Route::post('order/{id}/tracking',           [Admin\OrderController::class, 'orderTrackingUpdate']);

            /* Parcel Orders */
            Route::get('parcel-order/export',            [Admin\ParcelOrderController::class, 'fileExport']);
            Route::post('parcel-order/import',           [Admin\ParcelOrderController::class, 'fileImport']);
            Route::post('parcel-order/{id}/deliveryman', [Admin\ParcelOrderController::class, 'orderDeliverymanUpdate']);
            Route::post('parcel-order/{id}/status',      [Admin\ParcelOrderController::class, 'orderStatusUpdate']);
            Route::apiResource('parcel-orders',       Admin\ParcelOrderController::class);
            Route::delete('parcel-orders/delete',        [Admin\ParcelOrderController::class, 'destroy']);
            Route::get('parcel-orders/drop/all',         [Admin\ParcelOrderController::class, 'dropAll']);

            /* Parcel Options */
            Route::apiResource('parcel-options',    Admin\ParcelOptionController::class);
            Route::delete('parcel-options/delete',           [Admin\ParcelOptionController::class, 'destroy']);
            Route::get('parcel-options/drop/all',            [Admin\ParcelOptionController::class, 'dropAll']);

            /* Parcel Order Setting */
            Route::apiResource('parcel-order-settings',    Admin\ParcelOrderSettingController::class);
            Route::delete('parcel-order-settings/delete',    [Admin\ParcelOrderSettingController::class, 'destroy']);
            Route::get('parcel-order-settings/drop/all',     [Admin\ParcelOrderSettingController::class, 'dropAll']);

            /* Users */
            Route::get('users/search',                  [Admin\UserController::class, 'usersSearch']);
            Route::get('users/paginate',                [Admin\UserController::class, 'paginate']);
            Route::get('users/drop/all',                [Admin\UserController::class, 'dropAll']);
            Route::post('users/{uuid}/role/update',     [Admin\UserController::class, 'updateRole']);
            Route::get('users/{uuid}/wallets/history',  [Admin\UserController::class, 'walletHistories']);
            Route::post('users/{uuid}/wallets',         [Admin\UserController::class, 'topUpWallet']);
            Route::post('users/{uuid}/active',          [Admin\UserController::class, 'setActive']);
            Route::post('users/{uuid}/password',        [Admin\UserController::class, 'passwordUpdate']);
            Route::get('users/{uuid}/login-as',         [Admin\UserController::class, 'loginAsUser']);
            Route::apiResource('users',       Admin\UserController::class)->except(['index']);
            Route::delete('users/delete',               [Admin\UserController::class, 'destroy']);

            /* User Working Days */
            Route::apiResource('user-working-days', Admin\UserWorkingDayController::class);
            Route::delete('user-working-days/delete',   [Admin\UserWorkingDayController::class, 'destroy']);

            Route::get('roles', Admin\RoleController::class);

            /* Users Wallet Histories */
            Route::get('wallet/histories/paginate',     [Admin\WalletHistoryController::class, 'paginate']);
            Route::get('wallet/histories/drop/all',     [Admin\WalletHistoryController::class, 'dropAll']);
            Route::post('wallet/history/{uuid}/status/change', [Admin\WalletHistoryController::class, 'changeStatus']);
            Route::get('wallet/drop/all',               [Admin\WalletController::class, 'dropAll']);

            /* Subscriptions */
            Route::apiResource('subscriptions', Admin\SubscriptionController::class);
            Route::delete('subscriptions/delete',          [Admin\SubscriptionController::class, 'destroy']);
            Route::get('subscriptions/drop/all',           [Admin\SubscriptionController::class, 'dropAll']);

            /* Shop Subscriptions */
            Route::apiResource('shop-subscriptions',    Admin\ShopSubscriptionController::class);
            Route::delete('shop-subscriptions/delete',      [Admin\ShopSubscriptionController::class, 'destroy']);
            Route::get('shop-subscriptions/drop/all',       [Admin\ShopSubscriptionController::class, 'dropAll']);

            /* Point */
            Route::get('points/paginate',           [Admin\PointController::class, 'paginate']);
            Route::post('points/{id}/active',       [Admin\PointController::class, 'setActive']);
            Route::apiResource('points',  Admin\PointController::class);
            Route::delete('points/delete',          [Admin\PointController::class, 'destroy']);
            Route::get('points/drop/all',           [Admin\PointController::class, 'dropAll']);

            /* Payments */
            Route::post('payments/{id}/active/status', [Admin\PaymentController::class, 'setActive']);
            Route::apiResource('payments',           Admin\PaymentController::class)->except('store', 'delete');
            Route::get('payments/drop/all',            [Admin\PaymentController::class, 'dropAll']);

            /* Translations */
            Route::get('translations/paginate',         [Admin\TranslationController::class, 'paginate']);
            Route::post('translations/import',          [Admin\TranslationController::class, 'import']);
            Route::get('translations/export',           [Admin\TranslationController::class, 'export']);
            Route::apiResource('translations',Admin\TranslationController::class);
            Route::delete('translations/delete',        [Admin\TranslationController::class, 'destroy']);
            Route::get('translations/drop/all',         [Admin\TranslationController::class, 'dropAll']);

            /* Transaction */
            Route::get('transactions/paginate',     [Admin\TransactionController::class, 'paginate']);
            Route::get('transactions/{id}',         [Admin\TransactionController::class, 'show']);
            Route::post('transactions/{id}',        [Admin\TransactionController::class, 'update']);
            Route::get('transactions/drop/all',     [Admin\TransactionController::class, 'dropAll']);

            /* Payment To Partner */
            Route::apiResource('payment-to-partners',             Admin\PaymentToPartnerController::class)->except(['store', 'update']);
            Route::post('payment-to-partners/store/many',           [Admin\PaymentToPartnerController::class, 'storeMany']);
            Route::post('payment-to-partners/bookings/store/many',  [Admin\PaymentToPartnerController::class, 'bookingStoreMany']);
            Route::get('payment-to-partners/drop/all',              [Admin\PaymentToPartnerController::class, 'dropAll']);
            Route::get('payment-to-partners/restore/all',           [Admin\PaymentToPartnerController::class, 'restoreAll']);
            Route::get('payment-to-partners/truncate/db',           [Admin\PaymentToPartnerController::class, 'truncate']);

            /* Tickets */
            Route::get('tickets/paginate',          [Admin\TicketController::class, 'paginate']);
            Route::post('tickets/{id}/status',      [Admin\TicketController::class, 'setStatus']);
            Route::get('tickets/statuses',          [Admin\TicketController::class, 'getStatuses']);
            Route::apiResource('tickets',         Admin\TicketController::class);
            Route::get('tickets/drop/all',          [Admin\TicketController::class, 'dropAll']);

            /* FAQS */
            Route::get('faqs/paginate',                 [Admin\FAQController::class, 'paginate']);
            Route::post('faqs/{uuid}/active/status',    [Admin\FAQController::class, 'setActiveStatus']);
            Route::apiResource('faqs',                Admin\FAQController::class)->except('index');
            Route::delete('faqs/delete',                [Admin\FAQController::class, 'destroy']);
            Route::get('faqs/drop/all',                 [Admin\FAQController::class, 'dropAll']);

            /* Blogs */
            Route::get('blogs/paginate',                [Admin\BlogController::class, 'paginate']);
            Route::post('blogs/{uuid}/publish',         [Admin\BlogController::class, 'blogPublish']);
            Route::post('blogs/{uuid}/active/status',   [Admin\BlogController::class, 'setActiveStatus']);
            Route::apiResource('blogs',               Admin\BlogController::class)->except('index');
            Route::delete('blogs/delete',               [Admin\BlogController::class, 'destroy']);
            Route::get('blogs/drop/all',                [Admin\BlogController::class, 'dropAll']);

            /* Settings */
            Route::get('settings/system/information',   [Admin\SettingController::class, 'systemInformation']);
            Route::get('settings/system/cache/clear',   [Admin\SettingController::class, 'clearCache']);
            Route::apiResource('settings',            Admin\SettingController::class);
            Route::get('settings/drop/all',             [Admin\SettingController::class, 'dropAll']);

            Route::post('backup/history',               [Admin\BackupController::class, 'download']);
            Route::get('backup/history',                [Admin\BackupController::class, 'histories']);
            Route::get('backup/drop/all',               [Admin\BackupController::class, 'dropAll']);

            // Auto updates
            Route::post('/project-upload',              [Admin\ProjectController::class, 'projectUpload']);
            Route::post('/project-update',              [Admin\ProjectController::class, 'projectUpdate']);

            /* Stories */
            Route::apiResource('stories',             Admin\StoryController::class)->only(['index', 'show']);
            Route::delete('stories/delete',             [Admin\StoryController::class, 'destroy']);
            Route::get('stories/drop/all',              [Admin\StoryController::class, 'dropAll']);

            /* Order Statuses */
            Route::get('order-statuses',                [Admin\OrderStatusController::class, 'index']);
            Route::post('order-statuses/{id}/active',   [Admin\OrderStatusController::class, 'active']);
            Route::get('order-statuses/drop/all',       [Admin\OrderStatusController::class, 'dropAll']);

            /* Tags */
            Route::apiResource('tags',                Admin\TagController::class);
            Route::delete('tags/delete',                [Admin\TagController::class, 'destroy']);
            Route::get('tags/drop/all',                 [Admin\TagController::class, 'dropAll']);

            /* Email Setting */
            Route::apiResource('email-settings',  Admin\EmailSettingController::class);
            Route::delete('email-settings/delete',          [Admin\EmailSettingController::class, 'destroy']);
            Route::get('email-settings/set-active/{id}',    [Admin\EmailSettingController::class, 'setActive']);
            Route::get('email-settings/drop/all',           [Admin\EmailSettingController::class, 'dropAll']);

            /* Email Subscriptions */
            Route::get('email-subscriptions',          [Admin\EmailSubscriptionController::class, 'emailSubscriptions']);
            Route::post('email-subscriptions/{id}',    [Admin\EmailSubscriptionController::class, 'setActive']);
            Route::get('email-subscriptions/drop/all', [Admin\EmailSubscriptionController::class, 'dropAll']);

            /* DeliveryMan Setting */
            Route::get('deliveryman/paginate',             [Admin\DeliveryManController::class, 'paginate']);
            Route::get('deliveryman-settings/paginate',    [Admin\DeliveryManSettingController::class, 'paginate']);
            Route::delete('deliveryman-settings/delete',   [Admin\DeliveryManSettingController::class, 'destroy']);

            Route::apiResource('deliveryman-settings',   Admin\DeliveryManSettingController::class)
                ->except('index', 'destroy');

            /* Email Templates */
            Route::get('email-templates/types',            [Admin\EmailTemplateController::class, 'types']);
            Route::apiResource('email-templates',        Admin\EmailTemplateController::class);
            Route::delete('email-templates/delete',        [Admin\EmailTemplateController::class, 'destroy']);
            Route::get('email-templates/drop/all',         [Admin\EmailTemplateController::class, 'dropAll']);

            /* Order Refunds */
            Route::get('order-refunds/paginate',    [Admin\OrderRefundsController::class, 'paginate']);
            Route::delete('order-refunds/delete',   [Admin\OrderRefundsController::class, 'destroy']);
            Route::apiResource('order-refunds',   Admin\OrderRefundsController::class);
            Route::get('order-refunds/drop/all',    [Admin\OrderRefundsController::class, 'dropAll']);

            /* Shop Working Days */
            Route::get('shop-working-days/paginate',    [Admin\ShopWorkingDayController::class, 'paginate']);
            Route::apiResource('shop-working-days',   Admin\ShopWorkingDayController::class)->except('index', 'store');
            Route::delete('shop-working-days/delete',   [Admin\ShopWorkingDayController::class, 'destroy']);

            /* Shop Closed Days */
            Route::get('shop-closed-dates/paginate',    [Admin\ShopClosedDateController::class, 'paginate']);

            Route::apiResource('shop-closed-dates',   Admin\ShopClosedDateController::class)
                ->except('index', 'store');
            Route::delete('shop-closed-dates/delete',   [Admin\ShopClosedDateController::class, 'destroy']);
            Route::get('shop-closed-dates/drop/all',    [Admin\ShopClosedDateController::class, 'dropAll']);

            /* Notifications */
            Route::apiResource('notifications',   Admin\NotificationController::class);
            Route::delete('notifications/delete',   [Admin\NotificationController::class, 'destroy']);
            Route::get('notifications/drop/all',    [Admin\NotificationController::class, 'dropAll']);

            /* Payouts */
            Route::apiResource('payouts',         Admin\PayoutsController::class);
            Route::post('payouts/{id}/status',      [Admin\PayoutsController::class, 'statusChange']);
            Route::delete('payouts/delete',         [Admin\PayoutsController::class, 'destroy']);
            Route::get('payouts/drop/all',          [Admin\PayoutsController::class, 'dropAll']);

            /* Shop tags */
            Route::apiResource('shop-tags',        Admin\ShopTagController::class);
            Route::delete('shop-tags/delete',        [Admin\ShopTagController::class, 'destroy']);
            Route::get('shop-tags/drop/all',         [Admin\ShopTagController::class, 'dropAll']);

            /* PaymentPayload tags */
            Route::apiResource('payment-payloads', Admin\PaymentPayloadController::class);
            Route::delete('payment-payloads/delete', [Admin\PaymentPayloadController::class, 'destroy']);
            Route::get('payment-payloads/drop/all',  [Admin\PaymentPayloadController::class, 'dropAll']);

            /* SmsPayload tags */
            Route::apiResource('sms-payloads',     Admin\SmsPayloadController::class);
            Route::delete('sms-payloads/delete',     [Admin\SmsPayloadController::class, 'destroy']);
            Route::get('sms-payloads/drop/all',      [Admin\SmsPayloadController::class, 'dropAll']);

            /* Bonuses*/
            Route::get('bonuses',                    [Admin\BonusController::class, 'index']);

            Route::apiResource('referrals',        Admin\ReferralController::class);
            Route::get('referrals/transactions/paginate',   [Admin\ReferralController::class, 'transactions']);

            /* Report Categories */
            Route::get('categories/report/chart',   [Admin\CategoryController::class, 'reportChart']);

            /* Report Products */
            Route::get('products/report/chart',     [Admin\OrderReportController::class, 'reportChart']);
            Route::get('products/report/paginate',  [Admin\ProductController::class, 'reportPaginate']);

            /* Report Stocks */
            Route::get('stocks/report/paginate',    [Admin\ProductController::class, 'stockReportPaginate']);

            /* Report Orders */
            Route::get('orders/report/chart',        [Admin\OrderReportController::class, 'reportChart']);
            Route::get('orders/report/paginate',     [Admin\OrderReportController::class, 'reportChartPaginate']);
            Route::get('orders/report/transactions', [Admin\OrderReportController::class, 'reportTransactions']);

            /* Report Revenues */
            Route::get('revenue/report',      [Admin\OrderReportController::class, 'revenueReport']);

            /* Report Overviews */
            Route::get('overview/carts',      [Admin\OrderReportController::class, 'overviewCarts']);
            Route::get('overview/products',   [Admin\OrderReportController::class, 'overviewProducts']);
            Route::get('overview/categories', [Admin\OrderReportController::class, 'overviewCategories']);

            /* Shop Deliveryman Setting */
            Route::apiResource('shop-deliveryman-settings',Admin\ShopDeliverymanSettingController::class);
            Route::delete('shop-deliveryman-settings/delete',        [Admin\ShopDeliverymanSettingController::class, 'destroy']);
            Route::get('shop-deliveryman-settings/drop/all',         [Admin\ShopDeliverymanSettingController::class, 'dropAll']);

            /* Career */
            Route::apiResource('careers',Admin\CareerController::class);
            Route::delete('careers/delete',        [Admin\CareerController::class, 'destroy']);
            Route::get('careers/drop/all',         [Admin\CareerController::class, 'dropAll']);

            /* Pages */
            Route::apiResource('pages',Admin\PageController::class);
            Route::delete('pages/delete',        [Admin\PageController::class, 'destroy']);
            Route::get('pages/drop/all',         [Admin\PageController::class, 'dropAll']);

            /* User address */
            Route::apiResource('user-addresses',Admin\UserAddressController::class);
            Route::delete('user-addresses/delete',      [Admin\UserAddressController::class, 'destroy']);
            Route::get('user-addresses/drop/all',       [Admin\UserAddressController::class, 'dropAll']);

            /* Model logs */
            Route::get('model/logs/{id}',        [Admin\ModelLogController::class, 'show']);
            Route::get('model/logs/paginate',    [Admin\ModelLogController::class, 'paginate']);

            /* Regions */
            Route::apiResource('regions',      Admin\RegionController::class);
            Route::get('region/{id}/active',     [Admin\RegionController::class, 'changeActive']);
            Route::delete('regions/delete',      [Admin\RegionController::class, 'destroy']);
            Route::get('regions/drop/all',       [Admin\RegionController::class, 'dropAll']);

            /* Countries */
            Route::apiResource('countries',    Admin\CountryController::class);
            Route::get('country/{id}/active',    [Admin\CountryController::class, 'changeActive']);
            Route::delete('countries/delete',    [Admin\CountryController::class, 'destroy']);
            Route::get('countries/drop/all',     [Admin\CountryController::class, 'dropAll']);

            /* Cities */
            Route::apiResource('cities',       Admin\CityController::class);
            Route::get('city/{id}/active',       [Admin\CityController::class, 'changeActive']);
            Route::delete('cities/delete',       [Admin\CityController::class, 'destroy']);
            Route::get('cities/drop/all',        [Admin\CityController::class, 'dropAll']);

            /* Areas */
            Route::apiResource('areas',        Admin\AreaController::class);
            Route::get('area/{id}/active',       [Admin\AreaController::class, 'changeActive']);
            Route::delete('areas/delete',        [Admin\AreaController::class, 'destroy']);
            Route::get('areas/drop/all',         [Admin\AreaController::class, 'dropAll']);

            /* Ads Package */
            Route::apiResource('ads-packages',  Admin\AdsPackageController::class);
            Route::get('ads-package/{id}/active', [Admin\AdsPackageController::class, 'changeActive']);
            Route::delete('ads-packages/delete',  [Admin\AdsPackageController::class, 'destroy']);
            Route::get('ads-packages/drop/all',   [Admin\AdsPackageController::class, 'dropAll']);

            /* Shop Ads Package */
            Route::apiResource('shop-ads-packages', Admin\ShopAdsPackageController::class);

            /* RequestModel */
            Route::apiResource('request-models',   Admin\RequestModelController::class);
            Route::post('request-model/status/{id}', [Admin\RequestModelController::class, 'changeStatus']);
            Route::delete('request-models/delete',   [Admin\RequestModelController::class, 'destroy']);
            Route::get('request-models/drop/all',    [Admin\RequestModelController::class, 'dropAll']);

            /* Digital File */
            Route::apiResource('digital-files',  Admin\DigitalFileController::class);
            Route::get('digital-file/{id}/active', [Admin\DigitalFileController::class, 'changeActive']);
            Route::delete('digital-files/delete',  [Admin\DigitalFileController::class, 'destroy']);
            Route::get('digital-files/drop/all',   [Admin\DigitalFileController::class, 'dropAll']);

            /* Delivery prices */
            Route::apiResource('delivery-prices', Admin\DeliveryPriceController::class);
            Route::delete('delivery-prices/delete', [Admin\DeliveryPriceController::class, 'destroy']);
            Route::get('delivery-prices/drop/all',  [Admin\DeliveryPriceController::class, 'dropAll']);

            /* Delivery Point */
            Route::apiResource('delivery-points', Admin\DeliveryPointController::class);
            Route::delete('delivery-points/delete',   [Admin\DeliveryPointController::class, 'destroy']);
            Route::get('delivery-points/{id}/active', [Admin\DeliveryPointController::class, 'changeActive']);
            Route::get('delivery-points/drop/all',    [Admin\DeliveryPointController::class, 'dropAll']);

            /* Delivery Point Working Days */
            Route::apiResource('delivery-point-working-days', Admin\DeliveryPointWorkingDayController::class);
            Route::delete('delivery-point-working-days/delete',     [Admin\DeliveryPointWorkingDayController::class, 'destroy']);
            Route::get('delivery-point-working-days/{id}/disabled', [Admin\DeliveryPointWorkingDayController::class, 'changeDisabled']);
            Route::get('delivery-point-working-days/drop/all',      [Admin\DeliveryPointWorkingDayController::class, 'dropAll']);

            /* Delivery Point Closed Days */
            Route::apiResource('delivery-point-closed-dates', Admin\DeliveryPointClosedDateController::class);
            Route::delete('delivery-point-closed-dates/delete', [Admin\DeliveryPointClosedDateController::class, 'destroy']);
            Route::get('delivery-point-closed-dates/drop/all',  [Admin\DeliveryPointClosedDateController::class, 'dropAll']);

            /* Warehouse */
            Route::apiResource('warehouses',   Admin\WarehouseController::class);
            Route::delete('warehouses/delete',   [Admin\WarehouseController::class, 'destroy']);
            Route::get('warehouses/{id}/active', [Admin\WarehouseController::class, 'changeActive']);
            Route::get('warehouses/drop/all',    [Admin\WarehouseController::class, 'dropAll']);

            /* Warehouse Working Days */
            Route::apiResource('warehouse-working-days', Admin\WarehouseWorkingDayController::class);
            Route::delete('warehouse-working-days/delete',     [Admin\WarehouseWorkingDayController::class, 'destroy']);
            Route::get('warehouse-working-days/{id}/disabled', [Admin\WarehouseWorkingDayController::class, 'changeDisabled']);
            Route::get('warehouse-working-days/drop/all',      [Admin\WarehouseWorkingDayController::class, 'dropAll']);

            /* Warehouse Closed Days */
            Route::apiResource('warehouse-closed-dates', Admin\WarehouseClosedDateController::class);
            Route::delete('warehouse-closed-dates/delete', [Admin\WarehouseClosedDateController::class, 'destroy']);
            Route::get('warehouse-closed-dates/drop/all',  [Admin\WarehouseClosedDateController::class, 'dropAll']);

            /* Coupon */
            Route::get('coupons/paginate',  [Admin\CouponController::class, 'paginate']);
            Route::delete('coupons/delete', [Admin\CouponController::class, 'destroy']);
            Route::apiResource('coupons', Admin\CouponController::class);

            /* Gift Cart */
            Route::apiResource('gift-carts',   Admin\GiftCartController::class);
            Route::delete('gift-carts/delete',   [Admin\GiftCartController::class, 'destroy']);
            Route::get('gift-carts/drop/all',    [Admin\GiftCartController::class, 'dropAll']);

            /* User Gift Cart */
            Route::apiResource('user-gift-carts', Admin\UserGiftCartController::class)->only(['index', 'show']);

            /* Service */
            Route::apiResource('services',      Admin\ServiceController::class);
            Route::post('services/{id}/extras',   [Admin\ServiceController::class, 'extrasUpdate']);
            Route::post('services/{id}/faqs',     [Admin\ServiceController::class, 'faqsUpdate']);
            Route::delete('services/delete',      [Admin\ServiceController::class, 'destroy']);
            Route::get('services/drop/all',       [Admin\MasterClosedDateController::class, 'dropAll']);

            /* Service FAQ */
            Route::post('service-faqs/{id}/active/status',  [Admin\ServiceFaqController::class, 'setActiveStatus']);
            Route::apiResource('service-faqs',            Admin\ServiceFaqController::class);
            Route::delete('service-faqs/delete',            [Admin\ServiceController::class, 'destroy']);
            Route::get('service-faqs/drop/all',             [Admin\ServiceFaqController::class, 'dropAll']);

            /* Service Master */
            Route::apiResource('service-masters', Admin\ServiceMasterController::class);
            Route::delete('service-masters/delete', [Admin\ServiceMasterController::class, 'destroy']);
            Route::get('service-masters/drop/all',  [Admin\MasterClosedDateController::class, 'dropAll']);

            /* Service Master Notifications */
            Route::apiResource('service-master-notifications', Admin\ServiceMasterNotificationController::class);
            Route::delete('service-master-notifications/delete', [Admin\ServiceMasterNotificationController::class, 'destroy']);
            Route::get('service-master-notifications/drop/all', [Admin\ServiceMasterNotificationController::class, 'dropAll']);

            /* Master Closed Days */
            Route::apiResource('master-closed-dates', Admin\MasterClosedDateController::class);
            Route::delete('master-closed-dates/delete', [Admin\MasterClosedDateController::class, 'destroy']);
            Route::get('master-closed-dates/drop/all',  [Admin\MasterClosedDateController::class, 'dropAll']);

            /* Master Disabled Times */
            Route::apiResource('master-disabled-times', Admin\MasterDisabledTimeController::class);
            Route::delete('master-disabled-times/delete', [Admin\MasterDisabledTimeController::class, 'destroy']);
            Route::get('master-disabled-times/drop/all',  [Admin\MasterDisabledTimeController::class, 'dropAll']);

            /* Bookings */
            Route::apiResource('bookings',   Admin\BookingController::class);
            Route::get('bookings/{id}/get-all',        [Admin\BookingController::class, 'bookingsByParent']);
            Route::post('bookings/{id}/status/update', [Admin\BookingController::class, 'statusUpdate']);
            Route::post('bookings/{id}/notes/update',  [Admin\BookingController::class, 'notesUpdate']);
            Route::post('bookings/{id}/times/update',  [Admin\BookingController::class, 'timesUpdate']);
            Route::post('bookings/{id}/extra-time',    [Admin\BookingController::class, 'extraTime']);
            Route::post('bookings/calculate',          [Admin\BookingController::class, 'calculate']);
            Route::delete('bookings/delete',           [Admin\BookingController::class, 'destroy']);
            Route::get('bookings/drop/all',            [Admin\BookingController::class, 'dropAll']);
            Route::get('bookings/report/transactions', [Admin\BookingController::class, 'reportTransactions']);

            /* Bookings Report */
            Route::get('booking/reports/statistic',    [Admin\BookingReportController::class, 'statistic']);

            /* Memberships */
            Route::apiResource('memberships', Admin\MemberShipController::class);
            Route::delete('memberships/delete', [Admin\MemberShipController::class, 'destroy']);
            Route::get('memberships/drop/all',  [Admin\MemberShipController::class, 'dropAll']);

            /*User Membership */
            Route::apiResource('user-memberships', Admin\UserMemberShipController::class)->only(['index', 'show']);

            /* Admin Invite */
            Route::get('invites/paginate',            [Admin\InviteController::class, 'paginate']);
            Route::post('invites/{id}/status/change', [Admin\InviteController::class, 'changeStatus']);
            Route::post('invitation/link',            [Admin\InviteController::class, 'create']);
            Route::delete('invitations/delete',       [Admin\InviteController::class, 'delete']);

            /* Service Extra */
            Route::apiResource('service-extras',    Admin\ServiceExtraController::class)->except(['destroy']);
            Route::delete('service-extras/delete',      [Admin\ServiceExtraController::class, 'destroy']);
            Route::get('service-extras/drop/all',       [Admin\ServiceExtraController::class, 'dropAll']);

            /* Form-option */
            Route::apiResource('form-options', Admin\FormOptionController::class);
            Route::delete('form-options/delete', [Admin\FormOptionController::class, 'destroy']);
        });

    });

    Route::group(['prefix' => 'webhook'], function () {
        Route::any('stripe/payment',       [Payment\StripeController::class,      'paymentWebHook']);
        Route::any('orange/payment',       [Payment\OrangeController::class,      'paymentWebHook']);
        Route::any('mtn/payment',          [Payment\MtnController::class,         'paymentWebHook']);
        Route::any('razorpay/payment',     [Payment\RazorPayController::class,    'paymentWebHook']);
        Route::any('iyzico/payment',       [Payment\IyzicoController::class, 	  'processTransaction']);
        Route::any('paystack/payment',     [Payment\PayStackController::class,    'paymentWebHook']);
        Route::any('paytabs/payment',      [Payment\PayTabsController::class,     'paymentWebHook']);
        Route::any('flutter-wave/payment', [Payment\FlutterWaveController::class, 'paymentWebHook']);
        Route::any('paypal/payment',       [Payment\PayPalController::class,      'paymentWebHook']);
        Route::any('mercado-pago/payment', [Payment\MercadoPagoController::class, 'paymentWebHook']);
        Route::any('moya-sar/payment',     [Payment\MoyasarController::class,     'paymentWebHook']);
        Route::any('mollie/payment',       [Payment\MollieController::class,      'paymentWebHook']);
        Route::any('maksekeskus/payment',  [Payment\MaksekeskusController::class, 'paymentWebHook']);
    });
});

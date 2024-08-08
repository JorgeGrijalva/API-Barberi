<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\NotificationHelper;
use App\Models\Order;
use App\Models\Settings;
use App\Models\ShopLocation;
use App\Models\User;
use App\Traits\Loggable;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class AttachDeliveryMan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Loggable;

    public ?Order $order;
    public ?string $language;

    /**
     * Create a new event instance.
     *
     * @param Order|null $order
     * @param string|null $language
     */
    public function __construct(?Order $order, ?string $language)
    {
        $this->order    = $order;
        $this->language = $language;
    }

    /**
     * Handle the event
     * @return void
     */
    public function handle(): void
    {
        $result = [];

        try {
            $order = $this->order;

            $second = Settings::where('key', 'deliveryman_order_acceptance_time')->first();

            if (empty($order) || $order->delivery_type != Order::DELIVERY) {
                return;
            }

            $items = [];

            $shopLocations = ShopLocation::where('shop_id', $order->shop_id)
                ->select([
                    'region_id',
                    'country_id',
                    'city_id',
                    'area_id',
                ])
                ->get();

            $regionIds  = $shopLocations->whereNotNull('region_id')->pluck('region_id')->toArray();
            $countryIds = $shopLocations->whereNotNull('country_id')->pluck('country_id')->toArray();
            $cityIds    = $shopLocations->whereNotNull('city_id')->pluck('city_id')->toArray();
            $areaIds    = $shopLocations->whereNotNull('area_id')->pluck('area_id')->toArray();

            $users = User::with('deliveryManSetting')
                ->whereHas('deliveryManSetting', fn(Builder $query) => $query
                    ->where('online', 1)
                    ->when(count($regionIds)  > 0, fn($q) => $q->whereIn('region_id',  $regionIds))
                    ->when(count($countryIds) > 0, fn($q) => $q->whereIn('country_id', $countryIds))
                    ->when(count($cityIds)    > 0, fn($q) => $q->whereIn('city_id',    $cityIds))
                    ->when(count($areaIds)    > 0, fn($q) => $q->whereIn('area_id',    $areaIds))
                    ->where(function ($q) {

                        $time = date('Y-m-d H:i', strtotime('-15 minutes'));

                        $q->where('updated_at', '>=', $time)->orWhere('created_at', '>=', $time);

                    })
                )
                ->whereNotNull('firebase_token')
                ->select(['firebase_token', 'id'])
                ->get();

            $serverKey = Settings::where('key', 'server_key')->pluck('value')->first();

            $headers = [
                'Authorization' => "key=$serverKey",
                'Content-Type'  => 'application/json'
            ];

            foreach ($users as $user) {
                $items[] = [
                    'firebase_token' => $user->firebase_token,
                    'user'           => $user,
                ];
            }

            foreach (collect($items)->sort(SORT_ASC) as $item) {

                $deliveryMan = data_get(Order::select(['id', 'deliveryman_id'])->find($order->id), 'deliveryman_id');

                if (!empty($deliveryMan)) {
                    continue;
                }

                $token = data_get($item, 'firebase_token');

                $data = [
                    'registration_ids'  => (array)$token,
                    'notification'      => [
                        'title'         => "New order #$order->id",
                        'body'          => 'need attach deliveryman',
                    ],
                    'data'              => (new NotificationHelper)->deliveryManOrder($order)
                ];

                $result[] = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $data)->json();

                sleep((int)data_get($second, 'value', 30));
            }

        } catch (Exception $e) {
            $this->error($e);
        }

    }
}

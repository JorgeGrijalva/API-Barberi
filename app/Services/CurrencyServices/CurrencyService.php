<?php
declare(strict_types=1);

namespace App\Services\CurrencyServices;

use App\Helpers\ResponseError;
use App\Jobs\UpdateWalletCurrencyToDefault;
use App\Models\Currency;
use App\Services\CoreService;
use DB;
use Exception;
use Throwable;

class CurrencyService extends CoreService
{
    protected function getModelClass(): string
    {
        return Currency::class;
    }

    public function create(array $data): array
    {
        $first = $this->model()->first();

        try {
            /** @var Currency $currency */
            $currency = $this->model()->create($data);

            $first ?? $this->setCurrencyDefault($currency);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $currency];

        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Currency $currency
     * @param array $data
     * @return array
     */
    public function update(Currency $currency, array $data): array
    {
        try {
            $currency->update($data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $currency];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }
    }

    /**
     * @param array|null $ids
     * @return void
     */
    public function delete(?array $ids = []): void
    {
        foreach (Currency::whereIn('id', is_array($ids) ? $ids : [])->get() as $currency) {
            /** @var Currency $currency */

            if ($currency->default || Currency::count() === 1) {
                continue;
            }

            $currency->delete();
        }

    }


    public function setCurrencyDefault(Currency $currency) {

        DB::table('currencies')
            ->where('default', 1)
            ->update([
                'default' => 0,
            ]);

        $currency->default = 1;
        $currency->active = 1;
        $currency->save();

        UpdateWalletCurrencyToDefault::dispatchAfterResponse($currency);

    }

}

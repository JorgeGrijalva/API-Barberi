<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\ResponseError;
use App\Models\Currency;
use App\Models\Language;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\Loggable;
use Cache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

abstract class CoreService
{
    use ApiResponse, Loggable;

    protected object $model;
    protected string|int|null $currency;
    protected ?string $language;

    public function __construct()
    {
        $this->model    = app($this->getModelClass());
        $this->language = $this->setLanguage();
        $this->currency = $this->setCurrency();
    }

    abstract protected function getModelClass();

    protected function model(): Model|string|null|Application|User
    {
        return clone $this->model;
    }

    /**
     * @return string|null|int
     */
    protected function setCurrency(): int|string|null
    {
        return request(
            'currency_id',
            Currency::currenciesList()->where('default', 1)->first()?->id
        );
    }

    /**
     * @return string|null
     */
    protected function setLanguage(): string|null
    {
        return request(
            'lang',
            Language::where('default', 1)->first()?->locale,
        );
    }

    public function dropAll(?array $exclude = []): array
    {
        /** @var Model|Language $models */

        $models = $this->model();

        $models = $models
            ->when(
                data_get($exclude, 'column') && data_get($exclude, 'value'),
                function (Builder $query) use($exclude) {
                    $query->where(data_get($exclude, 'column'), '!=', data_get($exclude, 'value'));
                }
            )
            ->get();

        foreach ($models as $model) {

            try {

                $model->delete();

            } catch (Throwable $e) {

                $this->error($e);

            }

        }

        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function destroy(array $ids)
    {
        foreach ($this->model()->whereIn('id', $ids)->get() as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    public function delete(array $ids)
    {
        $this->destroy($ids);

        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    public function remove(array $ids, string $column = 'id', ?array $when = ['column' => null, 'value' => null])
    {
        $errorIds = [];

        $models = $this->model()
            ->whereIn($column, $ids)
            ->when(data_get($when, 'column'), fn($q, $column) => $q->where($column, data_get($when, 'value')))
            ->get();

        foreach ($models as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
                $errorIds[] = $model->id;
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_505,
            'message' => __(
                'errors.' . ResponseError::CANT_DELETE_IDS,
                [
                    'ids' => implode(', ', $errorIds)
                ],
                $this->language
            )
        ];
    }

}

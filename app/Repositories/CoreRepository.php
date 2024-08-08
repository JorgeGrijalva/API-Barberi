<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Currency;
use App\Traits\Loggable;
use App\Traits\SetCurrency;

abstract class CoreRepository
{
    use Loggable, SetCurrency;

    protected mixed $model;
    protected string|int|null $currency;
    protected ?string $language;
    protected string $updatedDate;

    /**
     * CoreRepository constructor.
     */
    public function __construct()
    {
        $this->model    = app($this->getModelClass());
        $this->language = request('lang');
        $this->currency = $this->setCurrency();
        $this->updatedDate = request('updated_at', '2021-01-01');
    }

    abstract protected function getModelClass();

    protected function model()
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

}

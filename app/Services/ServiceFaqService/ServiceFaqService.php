<?php
declare(strict_types=1);

namespace App\Services\ServiceFaqService;

use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\ServiceFaq;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Throwable;

class ServiceFaqService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return ServiceFaq::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $defaultLocale = Language::whereDefault(1)->first()?->locale;

        try {
            /** @var ServiceFaq $serviceFaq */
            $serviceFaq = $this->model()->create($data);

            $this->setSlug($serviceFaq, data_get($data, 'question'), $defaultLocale);
            $this->setQuestions($serviceFaq, $data);

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $serviceFaq
            ];

        } catch (Throwable $e) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
                'message'   => $e->getMessage()
            ];
        }
    }

    public function update(ServiceFaq $serviceFaq, array $data): array
    {
        $defaultLocale = Language::whereDefault(1)->first()?->locale;

        try {
            $serviceFaq->update($data);
            $this->setSlug($serviceFaq, data_get($data, 'question'), $defaultLocale);
            $this->setQuestions($serviceFaq, $data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $serviceFaq->fresh('translations')
            ];

        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }

    }

    public function setQuestions(ServiceFaq $serviceFaq, array $data): bool
    {
        if (!is_array(data_get($data, 'question'))) {
            return false;
        }

        if (!empty($serviceFaq->translations)) {
            $serviceFaq->translations()->delete();
        }

        foreach (data_get($data, 'question') as $index => $item) {

            $serviceFaq->translations()->create([
                'locale'    => $index,
                'question'  => $item,
                'answer'    => data_get($data, "answer.$index"),
            ]);
        }

        return true;
    }

    /**
     * @param int $id
     * @param int|null $shopId
     * @return array
     */
    public function setStatus(int $id, ?int $shopId = null): array
    {
        $serviceFaq = $this->model()->find($id);

        if (empty($serviceFaq) || !empty($shopId) && $serviceFaq->service->shop->id !== $shopId) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        /** @var ServiceFaq $serviceFaq */
        $serviceFaq->update(['active' => !$serviceFaq->active]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $serviceFaq];
    }

    /**
     * @param array $ids
     * @param int|null $shopId
     * @return array
     */
    public function delete(array $ids = [], ?int $shopId = null): array
    {
        $serviceFaqs = ServiceFaq::whereIn('id', $ids)
            ->when($shopId, fn($q, $shopId) => $q
                ->whereHas('service.shop', fn($q) => $q
                    ->where('shop_id', $shopId)))
            ->get();

        foreach ($serviceFaqs as $serviceFaq) {
            $serviceFaq->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }
}

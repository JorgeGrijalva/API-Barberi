<?php
declare(strict_types=1);

namespace App\Services\ExtraValueService;

use App\Helpers\ResponseError;
use App\Models\ExtraGroup;
use App\Models\ExtraValue;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class ExtraValueService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ExtraValue::class;
    }


    public function create(array $data): array
    {
        try {

            $group = ExtraGroup::find(data_get($data, 'extra_group_id'));

            if (empty($group)) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_404,
                    'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
                ];
            }

            /** @var ExtraGroup $group */

            $extraValue = $group->extraValues()->create($data);

            $images = data_get($data, 'images', []);

            if (is_array($images)) {
                /** @var ExtraValue $extraValue */
                $extraValue->galleries()->delete();
                $extraValue->uploads($images ?: []);
            }

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $extraValue,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }

    public function update(ExtraValue $extraValue, array $data): array
    {
        try {
            if ($extraValue->extra_group_id !== (int)data_get($data, 'extra_group_id')) {
                DB::table('stock_extras')
                    ->where('extra_value_id', $extraValue->id)
                    ->where('extra_group_id', $extraValue->extra_group_id)
                    ->update([
                        'extra_group_id' => (int)data_get($data, 'extra_group_id')
                    ]);
            }

            $extraValue->update($data);

            $images = data_get($data, 'images');

            if (is_array($images)) {
                $extraValue->galleries()->delete();

                $extraValue->uploads($images ?: []);
            }

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $extraValue->refresh(),
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    public function delete(?array $ids, ?int $shopId = null): void
    {
        $extraValues = $this->model()
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->when($shopId, fn($q) => $q->whereHas('group', fn($q) => $q->where('shop_id', $shopId)))
            ->get();

        foreach ($extraValues as $extraValue) {

            $stockExists = DB::table('stock_extras')
                ->select(['extra_value_id', 'stock_id'])
                ->where('extra_value_id', $extraValue->id)
                ->exists();

            if ($stockExists) {
                continue;
            }

            /** @var ExtraValue $extraValue */
            $stockExtras = DB::table('stock_extras')
                ->select(['extra_value_id', 'stock_id'])
                ->where('extra_value_id', $extraValue->id)
                ->pluck('stock_id')
                ->toArray();

            DB::table('stocks')->whereIn('id', $stockExtras)->delete();

            DB::table('stock_extras')->where('extra_value_id', $extraValue->id)->delete();

            $extraValue->delete();

        }
    }

    public function setActive(int $id): array
    {
        $extraValue = ExtraValue::find($id);

        if (empty($extraValue)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        /** @var ExtraValue $extraValue */
        $extraValue->update(['active' => !$extraValue->active]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $extraValue,
        ];
    }
}

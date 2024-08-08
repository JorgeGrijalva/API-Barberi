<?php
declare(strict_types=1);

namespace App\Services\ReferralService;

use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\Referral;
use App\Services\CoreService;
use Throwable;

class ReferralService extends CoreService
{
    protected function getModelClass(): string
    {
        return Referral::class;
    }

    public function create(array $data): array
    {
        try {
            $exist = Referral::first();

            if (date('H:i:s', strtotime(data_get($data, 'expired_at'))) == '00:00:00') {
                $data['expired_at'] = date('Y-m-d 23:59:59', strtotime(data_get($data, 'expired_at')));
            }

            $referral = Referral::updateOrCreate(['id' => data_get($exist, 'id')], $data);

            if (data_get($data, 'title.*')) {

                $this->setTranslations($referral, $data);

            }

            if (data_get($data, 'img')) {

                $referral->galleries()->delete();
                $referral->uploads([data_get($data, 'img')]);
                $referral->update(['img' => data_get($data, 'img')]);

            }

            $locale = Language::where('default', 1)->first()?->locale;

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $referral->loadMissing([
                    'translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'translations',
                    'galleries',
                ])
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_501];
        }
    }

    public function setTranslations(Referral $referral, array $data): Referral
    {
        $referral->translations()->delete();

        $title = data_get($data, 'title');

        foreach ($title as $index => $value) {

            $referral->translation()->create([
                'locale'        => $index,
                'title'         => $value,
                'description'   => data_get($data, "description.$index"),
                'faq'           => data_get($data, "faq.$index"),
            ]);

        }

        return $referral;
    }
}

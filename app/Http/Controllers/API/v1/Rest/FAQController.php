<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\FAQResource;
use App\Models\Faq;
use App\Models\Language;
use App\Models\PrivacyPolicy;
use App\Models\TermCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FAQController extends RestBaseController
{
    /**
     * Display a listing of the FAQ.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $faqs = Faq::with([
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ])
            ->where('active', 1)
            ->orderBy($request->input('column', 'id'), $request->input('sort', 'desc'))
            ->paginate($request->input('perPage', 10));

        return FAQResource::collection($faqs);
    }

    /**
     * Display Terms & Condition.
     *
     * @return JsonResponse
     */
    public function term(): JsonResponse
    {
        $model = TermCondition::with([
            'translation' => fn($query) => $query->where('locale', $this->language)
        ])->first();

        if (empty($model?->translation)) {

            $locale = Language::where('default', 1)->first()?->locale;

            $model = TermCondition::with([
                'translation' => fn($query) => $query->where('locale', $locale)
            ])->first();
        }

        if (empty($model?->translation)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $model);
    }

    /**
     * Display Terms & Condition.
     *
     * @return JsonResponse
     */
    public function policy(): JsonResponse
    {
        $model = PrivacyPolicy::with([
            'translation' => fn($query) => $query->where('locale', $this->language)
        ])->first();

        if (empty($model?->translation)) {

            $locale = Language::where('default', 1)->first()?->locale;

            $model = PrivacyPolicy::with([
                'translation' => fn($query) => $query->where('locale', $locale)
            ])->first();
        }

        if (empty($model?->translation)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $model);
    }

}

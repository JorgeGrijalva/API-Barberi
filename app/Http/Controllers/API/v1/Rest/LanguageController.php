<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends RestBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $languages = Language::orderByDesc('default')->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            LanguageResource::collection($languages)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $language = Language::find($id);

        if (empty($language)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            LanguageResource::make($language)
        );
    }

    /**
     * Get Language where "default = 1".
     *
     * @return JsonResponse
     */
    public function default(): JsonResponse
    {
        $language = Language::whereDefault(1)->first();

        if (empty($language)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            LanguageResource::make($language)
        );
    }

    /**
     * Get all Active languages
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        $languages = Language::where('active', 1)->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            LanguageResource::collection($languages)
        );
    }
}

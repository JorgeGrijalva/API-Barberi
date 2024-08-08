<?php
declare(strict_types=1);

namespace App\Traits;

use App\Helpers\ResponseError;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait OnResponse
{
    /**
     * @param array $result = ['code' => 200]
     * @return JsonResponse
     */
    public function onErrorResponse(array $result = []): JsonResponse
    {
        $code = $result['code'] ?? ResponseError::ERROR_101;

        $httpDefault = $code === ResponseError::ERROR_404 ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST;

        $http = $result['http'] ?? $httpDefault;

        $data = $result['data'] ?? [];

        $locale = property_exists($this, 'language') ? $this->language : 'en';

        return $this->errorResponse(
            (string)$code,
            (string)($result['message'] ?? __("errors.$code", $data, locale: $locale)),
            (int)$http
        );
    }
}

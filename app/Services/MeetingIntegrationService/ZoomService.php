<?php
declare(strict_types=1);

namespace App\Services\MeetingIntegrationService;

use App\Models\MasterDisabledTime;
use App\Services\CoreService;
use Firebase\JWT\JWT;
use JetBrains\PhpStorm\NoReturn;

class ZoomService extends CoreService
{
    protected function getModelClass(): string
    {
        return MasterDisabledTime::class;
    }

    public function createZoomMeeting($apiKey, $apiSecret, $topic, $startTime) {
        // URL для создания Zoom-комнаты
        $apiUrl = 'https://api.zoom.us/v2/users/me/meetings';

        $token = JWT::encode($apiKey, $apiSecret, $topic, $startTime);

        // Заголовки для запроса
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer $token"
        ];

        // Параметры для создания комнаты
        $params = [
            'topic'      => $topic,
            'type'       => 1,  // 1 - для плановой встречи
            'start_time' => $startTime,
            'duration'   => 60,  // Продолжительность в минутах
            'timezone'   => 'UTC'  // Часовой пояс
        ];

        // Опции для cURL
        $options = [
            CURLOPT_URL             => $apiUrl,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POST            => true,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_POSTFIELDS      => json_encode($params),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        dd($httpCode, json_decode($response, true));

    }


}

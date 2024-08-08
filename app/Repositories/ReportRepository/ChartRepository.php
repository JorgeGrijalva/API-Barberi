<?php
declare(strict_types=1);

namespace App\Repositories\ReportRepository;

class ChartRepository
{
    /**
     * @param $chart
     * @param string $key
     * @return array
     */
    final static function chart($chart, string $key): array
    {
        $result = [];

        foreach ($chart as $item) {

            if (!is_array($item)) {
                $item = (array)$item;
            }

            $time = $item['time'] ?? null;

            if (empty($time)) {
                continue;
            }

            $value = $item[$key] ?? null;
            $value = $key === 'count' && $value === 1 ? 1 : $value;

            if (!isset($result[$time])) {

                $result[$time] = [
                    'time' => $time,
                    $key   => $value
                ];

                continue;

            }

            $result[$time] = [
                'time' => $time,
                $key   => data_get($result, "$time.$key", 0) + ($value)
            ];

        }

        return array_values($result);
    }
}

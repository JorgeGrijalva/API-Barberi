<?php
declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

trait Loggable
{
    /**
     * @param Throwable $e
     * @return void
     */
    public function error(Throwable $e): void
    {
        Log::error($e->getMessage(), [
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        //  'trace'   => $e->getTrace(),
        ]);
    }
}


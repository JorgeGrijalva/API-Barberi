<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

abstract class RestBaseController extends Controller
{
    use ApiResponse;
}

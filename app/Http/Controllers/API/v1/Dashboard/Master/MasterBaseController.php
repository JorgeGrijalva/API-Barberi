<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Master;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

abstract class MasterBaseController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(['sanctum.check', 'role:master']);
    }
}

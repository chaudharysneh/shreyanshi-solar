<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class ApiBaseController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        // Prevent browser caching of API responses
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

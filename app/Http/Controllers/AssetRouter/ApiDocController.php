<?php

namespace App\Http\Controllers\AssetRouter;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ApiDocController extends Controller
{
    public function __invoke(): View
    {
        return view('asset-router.api');
    }
}

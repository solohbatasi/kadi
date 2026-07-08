<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Operations\PreLiveCheckService;
use Inertia\Inertia;
use Inertia\Response;

class PreLiveCheckController extends Controller
{
    public function __invoke(PreLiveCheckService $checks): Response
    {
        return Inertia::render('Admin/PreLiveCheck/Index', [
            'results' => $checks->results(),
        ]);
    }
}


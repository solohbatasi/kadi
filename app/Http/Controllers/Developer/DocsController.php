<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Developer/Docs/Index');
    }
}


<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    public function terms(): Response
    {
        return Inertia::render('Public/Legal/Terms');
    }

    public function privacy(): Response
    {
        return Inertia::render('Public/Legal/Privacy');
    }

    public function acceptableUse(): Response
    {
        return Inertia::render('Public/Legal/AcceptableUse');
    }

    public function security(): Response
    {
        return Inertia::render('Public/Legal/Security');
    }
}


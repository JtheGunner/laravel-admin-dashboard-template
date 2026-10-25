<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class IconsController extends Controller {
    public function __invoke(): Response {
        return Inertia::render('icons/Index');
    }
}

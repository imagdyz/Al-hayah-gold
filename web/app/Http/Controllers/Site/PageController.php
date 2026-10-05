<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function show(string $page)
    {
        return view('site.pages.'.$page);
    }
}

<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function exclusiveOffers()
    {
        return view('pages.exclusive-offers');
    }

    public function privacyPolicy()
    {
        return view('pages.privacy-policy');
    }

    public function contact()
    {
        return view('pages.contact');
    }
}

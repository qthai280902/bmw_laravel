<?php

namespace App\Http\Controllers;

use App\Models\Product;

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
        $bookableProducts = Product::query()
            ->active()
            ->vehicles()
            ->select(['id', 'name', 'type'])
            ->orderBy('name')
            ->get();

        return view('pages.contact', compact('bookableProducts'));
    }
}

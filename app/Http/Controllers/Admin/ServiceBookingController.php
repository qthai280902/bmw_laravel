<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ServiceBookingController extends Controller
{
    public function index(): View
    {
        return view('admin.service-bookings.index');
    }
}

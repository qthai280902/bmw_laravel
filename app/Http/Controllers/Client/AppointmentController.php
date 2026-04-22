<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * Danh sách lịch hẹn đã đặt.
     */
    public function index()
    {
        $appointments = Auth::user()
            ->appointments()
            ->with('product')
            ->latest()
            ->paginate(10);

        return view('client.appointments.index', compact('appointments'));
    }

    /**
     * Lưu lịch hẹn mới.
     */
    public function store(StoreAppointmentRequest $request)
    {
        Auth::user()->appointments()->create($request->validated());

        return back()->with('success', 'Bạn đã đặt lịch hẹn thành công. Chúng tôi sẽ sớm liên hệ xác nhận.');
    }
}

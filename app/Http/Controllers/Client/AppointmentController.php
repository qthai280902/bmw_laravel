<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Product;
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
     * Hiển thị form đặt lịch / báo giá.
     */
    public function create()
    {
        $products = Product::where('is_active', true)->get();

        return view('appointments.create', compact('products'));
    }

    /**
     * Lưu lịch hẹn mới.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $data = $request->validated();

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }

        Appointment::create($data);

        return redirect()->route('appointments.success')->with('success', 'Bạn đã đặt lịch hẹn thành công. Chúng tôi sẽ sớm liên hệ xác nhận.');
    }

    /**
     * Trang success
     */
    public function success()
    {
        return view('appointments.success');
    }
}

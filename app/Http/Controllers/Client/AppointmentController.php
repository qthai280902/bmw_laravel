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

        // Merge showroom vào meta_data nếu có
        $meta = $data['meta_data'] ?? [];

        if (! empty($data['showroom'])) {
            $meta['showroom'] = $data['showroom'];
        }

        // Merge Aftersales service fields vào meta_data
        if (in_array($data['type'], ['maintenance', 'detailing', 'car_wash'])) {
            if (! empty($data['customer_car_model'])) {
                $meta['customer_car_model'] = $data['customer_car_model'];
            }
            if (! empty($data['customer_car_condition'])) {
                $meta['customer_car_condition'] = $data['customer_car_condition'];
            }
        }

        $data['meta_data'] = ! empty($meta) ? $meta : null;

        // Xóa các trường không thuộc bảng appointments
        unset($data['showroom'], $data['customer_car_model'], $data['customer_car_condition']);

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

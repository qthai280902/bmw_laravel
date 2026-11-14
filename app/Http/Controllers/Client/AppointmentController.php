<?php

namespace App\Http\Controllers\Client;

use App\Events\AppointmentCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Product;
use App\Services\Ai\AiConversationTracker;
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
        $products = Product::query()
            ->active()
            ->vehicles()
            ->orderBy('name')
            ->get();

        return view('appointments.create', compact('products'));
    }

    /**
     * Lưu lịch hẹn mới.
     */
    public function store(StoreAppointmentRequest $request, AiConversationTracker $tracker)
    {
        $data = $request->validated();

        if (Auth::check()) {
            $user = Auth::user();
            $data['user_id'] = $user->id;
            $data['guest_name'] = ! empty($data['guest_name']) ? $data['guest_name'] : $user->name;
            $data['guest_email'] = ! empty($data['guest_email']) ? $data['guest_email'] : $user->email;
        }

        // Merge showroom vào meta_data nếu có (đồng thời giữ cột showroom)
        $meta = $data['meta_data'] ?? [];

        if (! empty($data['showroom'])) {
            $meta['showroom'] = $data['showroom'];
        }

        // Merge Aftersales service / Trade-in vehicle info vào meta_data nếu có
        if (! empty($data['customer_car_model'])) {
            $meta['customer_car_model'] = $data['customer_car_model'];
        }
        if (! empty($data['customer_car_condition'])) {
            $meta['customer_car_condition'] = $data['customer_car_condition'];
        }

        $data['meta_data'] = ! empty($meta) ? $meta : null;

        // Xóa các trường không thuộc bảng appointments
        unset($data['customer_car_model'], $data['customer_car_condition']);

        $appointment = Appointment::create($data);

        $tracker->linkCustomerTouchpoint($request, 'appointment', $appointment->id, $data['ai_visitor_id'] ?? null, [
            'name' => $appointment->guest_name ?? Auth::user()?->name,
            'email' => $appointment->guest_email ?? Auth::user()?->email,
            'phone' => $appointment->guest_phone,
        ]);

        event(new AppointmentCreated($appointment));

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

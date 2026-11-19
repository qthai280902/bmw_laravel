<?php

namespace App\Http\Controllers\Client;

use App\Events\AppointmentCreated;
use App\Http\Controllers\Ai\ShowroomAssistantController;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Services\Ai\AiConversationTracker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

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
        return view('appointments.create');
    }

    /**
     * Lưu lịch hẹn mới.
     */
    public function store(StoreAppointmentRequest $request, AiConversationTracker $tracker)
    {
        $data = $request->validated();

        if (empty($data['ai_visitor_id'])) {
            $cookieVisitorId = $request->cookie(ShowroomAssistantController::VISITOR_COOKIE_NAME);
            if (is_string($cookieVisitorId) && preg_match('/^[A-Za-z0-9._:-]{6,80}$/', trim($cookieVisitorId)) === 1) {
                $data['ai_visitor_id'] = trim($cookieVisitorId);
            }
        }

        if (! empty($data['ai_visitor_id'])) {
            Context::add('ai_visitor_id', $data['ai_visitor_id']);
        }

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
        if (! empty($data['service_package'])) {
            $meta['service_package'] = $data['service_package'];
        }

        $data['meta_data'] = ! empty($meta) ? $meta : null;

        // Xóa các trường không thuộc bảng appointments
        unset($data['customer_car_model'], $data['customer_car_condition'], $data['service_package']);

        $appointment = Appointment::create($data);

        $visitorId = $data['ai_visitor_id'] ?? null;
        $touchpointCustomer = [
            'name' => $appointment->guest_name ?? Auth::user()?->name,
            'email' => $appointment->guest_email ?? Auth::user()?->email,
            'phone' => $appointment->guest_phone,
        ];

        defer(function () use ($tracker, $request, $appointment, $visitorId, $touchpointCustomer): void {
            $tracker->linkCustomerTouchpoint($request, 'appointment', $appointment->id, $visitorId, $touchpointCustomer);
            event(new AppointmentCreated($appointment));
        });

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

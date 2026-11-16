<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Rule;

class AppointmentController extends Controller
{
    /**
     * Danh sách tất cả lịch hẹn.
     */
    public function index()
    {
        return view('admin.appointments.index');
    }

    /**
     * Cập nhật trạng thái lịch hẹn.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
        ]);

        $appointment->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái lịch hẹn thành công.');
    }
}

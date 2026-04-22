<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * Danh sách tất cả lịch hẹn.
     */
    public function index(Request $request)
    {
        $query = Appointment::with(['user', 'product'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $appointments = $query->paginate(15)->withQueryString();

        return view('admin.appointments.index', compact('appointments'));
    }

    /**
     * Cập nhật trạng thái lịch hẹn.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => ['required', 'string'],
        ]);

        $appointment->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái lịch hẹn thành công.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $customers = Appointment::select(
            DB::raw('COALESCE(guest_email, users.email) as email'),
            DB::raw('MAX(COALESCE(guest_name, users.name)) as name'),
            DB::raw('MAX(COALESCE(guest_phone, "N/A")) as phone'),
            DB::raw('COUNT(*) as interactions_count'),
            DB::raw('MAX(appointment_date) as last_interaction')
        )
        ->leftJoin('users', 'appointments.user_id', '=', 'users.id')
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%")
                  ->orWhere('guest_phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        })
        ->groupBy(DB::raw('COALESCE(guest_email, users.email)'))
        ->orderBy('last_interaction', 'desc')
        ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }
}

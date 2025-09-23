<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index()
    {
        Gate::authorize('bookings-manage');

        // Will implement property management later
        return response()->json(['success' => true]);
    }
}

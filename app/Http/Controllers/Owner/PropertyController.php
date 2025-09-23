<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyController extends Controller
{
    public function index()
    {
        Gate::authorize('properties-manage');

        // Will implement property management later
        return response()->json(['success' => true]);
    }
}

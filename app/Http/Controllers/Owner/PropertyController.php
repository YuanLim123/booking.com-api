<?php

namespace App\Http\Controllers\Owner;

use App\Models\Property;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
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

    public function store(StorePropertyRequest $request)
    {
        Gate::authorize('properties-manage');

        $property = Property::create($request->validated());

        return $property;
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Models\Property;
use App\Http\Resources\PropertySearchResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Property $property, Request $request)
    {
        if ($request->adults && $request->children) {
            $property->load(['apartments' => function ($query) use ($request) {
                $query->where('capacity_adults', '>=', $request->adults)
                    ->where('capacity_children', '>=', $request->children)
                    ->orderBy('capacity_adults')
                    ->orderBy('capacity_children');
            }, 'apartments.facilities']);
        } else {
            $property->load(['apartments.facilities']);
        }

        return new PropertySearchResource($property);
    }
}

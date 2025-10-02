<?php

namespace App\Http\Controllers\Public;

use App\Models\Facility;
use App\Models\Geoobject;
use App\Models\Property;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertySearchResource;
use Illuminate\Http\Request;

class PropertySearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $properties = Property::query()
            ->with([
                'city',
                'apartments.apartmentType',
                'apartments.rooms.beds.bedType',
                'media' => function ($query) {
                    $query
                        ->orderBy('position');
                },
            ])
            ->when($request->city, function ($query) use ($request) {
                $query->where('city_id', $request->city);
            })
            ->when($request->country, function ($query) use ($request) {
                $query->whereHas('city', function ($q) use ($request) {
                    $q->where('country_id', $request->country);
                });
            })
            ->when($request->geoobject, function ($query) use ($request) {
                $geoobject = Geoobject::find($request->geoobject);
                if ($geoobject && env('DB_CONNECTION', 'sqlite') === 'mysql') {
                    $condition = "(
                        6371 * acos(
                            cos(radians(" . $geoobject->lat . "))
                            * cos(radians(`lat`))
                            * cos(radians(`long`) - radians(" . $geoobject->long . "))
                            + sin(radians(" . $geoobject->lat . ")) * sin(radians(`lat`))
                        ) < 10
                    )";
                    $query->whereRaw($condition);
                }
            })
            ->when($request->adults && $request->children, function ($query) use ($request) {
                $query->withWherehas('apartments', function ($query) use ($request) {
                    $query->where('capacity_adults', '>=', $request->adults)
                        ->where('capacity_children', '>=', $request->children)
                        ->orderBy('capacity_adults')
                        ->orderBy('capacity_children')
                        ->take(1);
                });
            })
            ->when($request->facilities, function ($query) use ($request) {
                $query->whereHas('facilities', function ($query) use ($request) {
                    $query->whereIn('id', $request->facilities);
                });
            })
            ->get();
        $facilities = Facility::query()
            ->whereNull('category_id')
            ->withCount(['properties' => function ($property) use ($properties) {
                $property->whereIn('id', $properties->pluck('id'));
            }])
            ->where('properties_count', '>', 0)
            ->get()
            ->pluck('properties_count', 'name');
        return [
            'properties' => PropertySearchResource::collection($properties),
            'facilities' => $facilities,
        ];
    }
}

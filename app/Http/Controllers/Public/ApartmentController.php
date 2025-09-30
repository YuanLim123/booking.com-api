<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentSearchResource;
use App\Http\Resources\ApartmentDetailsResource;
use App\Models\Apartment;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function __invoke(Apartment $apartment)
    {
        $apartment->load('facilities.category');
 
        // put it in model class as getFacilityCategoriesAttribute
        // $apartment->setAttribute(
        //     'facility_categories',
        //     $apartment->facilities->groupBy('category.name')->mapWithKeys(fn ($items, $key) => [$key => $items->pluck('name')])
        // );
 
        return new ApartmentDetailsResource($apartment);
    }
}

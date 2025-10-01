<?php

namespace App\Http\Controllers\Owner;

use App\Models\Property;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PropertyPhotoController extends Controller
{
    public function show(Property $property)
    {
        dd($property->getFirstMedia('photos')->getFullUrl(), $property->getFirstMedia('photos')->getUrl());
    }

    public function store(Property $property, Request $request)
    {
        $request->validate([
            'photo' => ['image', 'max:5000']
        ]);

        if ($property->owner_id != auth()->id()) {
            abort(403);
        }

        $photo = $property->addMediaFromRequest('photo')->toMediaCollection('photos');

        return [
            'filename' => $photo->getFullUrl(),
            'thumbnail' => $photo->getFullUrl('thumbnail')
        ];
    }
}

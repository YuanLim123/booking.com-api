<?php

namespace App\Http\Controllers\Owner;

use App\Models\Property;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

        $position = Media::query()
            ->where('model_type', 'App\Models\Property')
            ->where('model_id', $property->id)
            ->max('position') + 1;

        $photo->position = $position;
        $photo->save();

        return [
            'filename' => $photo->getFullUrl(),
            'thumbnail' => $photo->getFullUrl('thumbnail'),
            'position' => $photo->position
        ];
    }

    public function destroy(Property $property)
    {
        if ($property->owner_id != auth()->id()) {
            abort(403);
        }

        $property->clearMediaCollection('photos');

        return response()->noContent();
    }

    public function reorder(Property $property, Media $photo, int $newPosition)
    {
        if ($property->owner_id != auth()->id() || $photo->model_id != $property->id) {
            abort(403);
        }

        $maximumNewPositionAllowed = Media::query()
            ->where('model_type', 'App\Models\Property')
            ->where('model_id', $property->id)
            ->max('position');

        $minimumNewPositionAllowed = 1;
        
        if ($newPosition < $minimumNewPositionAllowed || $newPosition > $maximumNewPositionAllowed) {
            abort(422, 'The new position is out of valid range.');
        }

        $query = Media::query()
            ->where('model_type', 'App\Models\Property')
            ->where('model_id', $photo->model_id);

        if ($newPosition < $photo->position) {
            $query
                ->whereBetween('position', [$newPosition, $photo->position - 1])
                ->increment('position');
        } else {
            $query
                ->whereBetween('position', [$photo->position + 1, $newPosition])
                ->decrement('position');
        }

        $photo->position = $newPosition;
        $photo->save();

        return [
            'newPosition' => $photo->position
        ];
    }

    public function reorderWithSpatieOrdering(Property $property, Request $request)
    {
        if ($property->owner_id != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:media,id']
        ]);

        $mediaIds = Media::query()
            ->where('model_type', 'App\Models\Property')
            ->where('model_id', $property->id)
            ->pluck('id');


        if (! Arr::every($request->order, fn ($id) => $mediaIds->contains($id))) {
            abort(422, 'One or more media IDs are invalid.');
        }

        Media::setNewOrder($request->order);

        return [
            'message' => 'Photos reordered successfully',
            'order' => $request->order
        ];
    }
}

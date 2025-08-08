<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $query = Offer::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $columns = Schema::getColumnListing('offers');

            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });

            $data = $query->latest()->paginate($request->input('per_page', 10));

            return response()->json($data, 200);
        }

        $data = Offer::latest()->paginate($request->input('per_page', 10));

        $data->getCollection()->transform(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'category_id' => optional($offer->category)->title,
                'cat_id' => optional($offer->category)->id,
                'images' => $offer->getMedia('offers')->map(function ($media) {
                    return $media->getUrl();
                }),
            ];
        });

        return response()->json($data, 200);
    }

    public function all(Request $request)
    {
        $data = Offer::latest()->get();

        $data = $data->map(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'category_id' => optional($offer->category)->title,
                'cat_id' => optional($offer->category)->id,
                'images' => $offer->getMedia('offers')->map(function ($media) {
                    return $media->getUrl();
                }),
            ];
        });

        return response()->json(['data' => $data], 200);
    }



    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'title' => 'required|string',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:12048',
        ]);

        $offer = Offer::create([
            'category_id' => $request->category_id,
            'title' => $request->title
        ]);

        foreach ($request->file('images') as $image) {
            $offer->addMedia($image)->toMediaCollection('offers');
        }

        return response()->json(['message' => 'تم إضافة العرض بنجاح.'], 201);
    }


    public function show(string $id)
    {
        $offer = Offer::findOrFail($id);

        return response()->json([
            'data' => [
                ...$offer->toArray(),
                'images' => $offer->getMedia('offers')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                    ];
                }),
            ],
        ], 200);
    }


    public function update(Request $request, string $id)
    {
        if ($request->has('deleted_images') && is_string($request->deleted_images)) {
            $request->merge([
                'deleted_images' => json_decode($request->deleted_images, true),
            ]);
        }

        $request->validate([
            'category_id' => 'required',
            'title' => 'string',
            'deleted_images' => 'array',
            'deleted_images.*' => 'integer',
            'images' => 'array|nullable',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:12048',
        ]);

        $offer = Offer::findOrFail($id);

        DB::transaction(function () use ($request, $offer) {
            $offer->update($request->except(['images', 'deleted_images']));

            if (!empty($request->deleted_images)) {
                foreach ($request->deleted_images as $mediaId) {
                    $media = $offer->media()->where('id', $mediaId)->first();
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $offer->addMedia($image)->toMediaCollection('offers');
                }
            }
        });

        return response()->json(['message' => 'تم تحديث بيانات العرض بنجاح.'], 200);
    }


    public function destroy(string $id)
    {
        $offer = Offer::findOrFail($id);

        $offer->clearMediaCollection('offers');

        Offer::destroy($id);

        return response()->json(['message' => 'تم حذف العرض بنجاح.'], 200);
    }
}

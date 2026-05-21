<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $channels = Channel::active()
            ->online()
            ->with(['country', 'category'])
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(30)
            ->get();

        $countries = Country::where('name', 'like', "%{$query}%")
            ->withCount(['channels' => fn($q) => $q->active()->online()])
            ->get();

        return response()->json([
            'channels' => $channels,
            'countries' => $countries,
            'query' => $query,
            'total_channels' => $channels->count(),
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $channels = Channel::active()
            ->online()
            ->where('name', 'like', "{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->pluck('name');

        return response()->json($channels);
    }
}

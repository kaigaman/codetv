<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Country;
use App\Services\StreamValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Channel::query()
            ->active()
            ->online()
            ->with(['country', 'category', 'languages']);

        if ($request->filled('country')) {
            $query->byCountry($request->country);
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('language')) {
            $query->whereHas('languages', fn($q) => $q->where('code', $request->language));
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->boolean('hd')) {
            $query->hd();
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $channels = $query->orderBy('name')->paginate($perPage);

        return response()->json($channels);
    }

    public function show(Channel $channel): JsonResponse
    {
        if (!$channel->is_active) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $channel->load(['country', 'category', 'languages']);
        return response()->json($channel);
    }

    public function byCountry(string $code): JsonResponse
    {
        $country = Country::where('code', $code)->first();
        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }

        $channels = Channel::active()
            ->online()
            ->where('country_id', $country->id)
            ->with(['category', 'languages'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'country' => $country,
            'channels' => $channels,
            'total' => $channels->count(),
        ]);
    }

    public function uganda(): JsonResponse
    {
        $country = Country::where('code', 'ug')->first();
        if (!$country) {
            return response()->json(['error' => 'Uganda not found in database'], 404);
        }

        $channels = Channel::active()
            ->online()
            ->where('country_id', $country->id)
            ->with(['category', 'languages'])
            ->orderBy('name')
            ->get();

        $categories = $channels->groupBy(fn($c) => $c->category?->name ?? 'Other');

        return response()->json([
            'country' => ['code' => 'ug', 'name' => 'Uganda'],
            'channels' => $channels,
            'by_category' => $categories->map(fn($group, $key) => [
                'category' => $key,
                'count' => $group->count(),
                'channels' => $group,
            ])->values(),
            'total' => $channels->count(),
        ]);
    }

    public function ugandaWorking(): JsonResponse
    {
        $country = Country::where('code', 'ug')->first();
        if (!$country) {
            return response()->json(['error' => 'Uganda not found in database'], 404);
        }

        $channels = Channel::active()
            ->online()
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '')
            ->where('country_id', $country->id)
            ->with(['category', 'languages'])
            ->orderBy('name')
            ->get();

        $categories = $channels->groupBy(fn($c) => $c->category?->name ?? 'Other');

        return response()->json([
            'country' => ['code' => 'ug', 'name' => 'Uganda'],
            'channels' => $channels,
            'by_category' => $categories->map(fn($group, $key) => [
                'category' => $key,
                'count' => $group->count(),
                'channels' => $group,
            ])->values(),
            'total' => $channels->count(),
            'verified_only' => true,
        ]);
    }

    public function random(Request $request): JsonResponse
    {
        $channel = Channel::active()
            ->online()
            ->with(['country', 'category'])
            ->inRandomOrder()
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'No channels available'], 404);
        }

        return response()->json($channel);
    }
}

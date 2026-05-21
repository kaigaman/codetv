<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    public function index(): JsonResponse
    {
        $countries = Country::where('is_active', true)
            ->withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json($countries);
    }

    public function show(string $code): JsonResponse
    {
        $country = Country::where('code', $code)
            ->withCount(['channels' => fn($q) => $q->active()->online()])
            ->first();

        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }

        return response()->json($country);
    }
}

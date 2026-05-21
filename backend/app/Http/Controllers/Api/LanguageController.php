<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    public function index(): JsonResponse
    {
        $languages = Language::withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json($languages);
    }
}

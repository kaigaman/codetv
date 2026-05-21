<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }
}

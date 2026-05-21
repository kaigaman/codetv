<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $channels = Channel::whereIn('id', $user->favorites()->pluck('channel_id'))
            ->active()
            ->online()
            ->with(['country', 'category'])
            ->orderBy('name')
            ->get();

        return response()->json($channels);
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['channel_id' => 'required|exists:channels,id']);

        $user = $request->user();
        $favorite = Favorite::where('user_id', $user->id)
            ->where('channel_id', $request->channel_id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['favorited' => false]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'channel_id' => $request->channel_id,
        ]);

        return response()->json(['favorited' => true]);
    }

    public function check(Request $request): JsonResponse
    {
        $request->validate(['channel_id' => 'required|exists:channels,id']);
        $user = $request->user();

        $favorited = Favorite::where('user_id', $user->id)
            ->where('channel_id', $request->channel_id)
            ->exists();

        return response()->json(['favorited' => $favorited]);
    }
}

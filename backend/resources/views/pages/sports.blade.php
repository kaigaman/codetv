@extends('layouts.app')

@section('title', 'Sports Channels — CODETV')

@section('content')
<div class="bg-gradient-to-b from-blue-900/10 via-gray-950 to-gray-950">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex items-center gap-4 mb-8">
            <span class="text-5xl flex items-center justify-center w-12 h-12 bg-blue-900/30 rounded-full">
                <i class="fas fa-futbol text-blue-400 text-2xl"></i>
            </span>
            <div>
                <h1 class="text-3xl font-bold">Sports &amp; Soccer Channels</h1>
                <p class="text-gray-400 mt-1">
                    Live sports from around the world —
                    <strong class="text-blue-400">{{ $channels->total() }}</strong> channels
                    <span class="text-gray-600">| <strong class="text-green-400">{{ $online }}</strong> verified working</span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-6">
            <a href="{{ route('sports') }}" class="px-4 py-2 @if(!$league && !$search) bg-blue-600 text-white @else bg-gray-800 hover:bg-gray-700 @endif rounded-full text-sm transition">
                All Sports
            </a>
            <a href="{{ route('sports', ['league' => 'premier-league']) }}" class="px-4 py-2 @if($league === 'premier-league') bg-blue-600 text-white @else bg-gray-800 hover:bg-gray-700 @endif rounded-full text-sm transition">
                Premier League
            </a>
            <a href="{{ route('sports', ['league' => 'laliga']) }}" class="px-4 py-2 @if($league === 'laliga') bg-blue-600 text-white @else bg-gray-800 hover:bg-gray-700 @endif rounded-full text-sm transition">
                La Liga
            </a>
            <a href="{{ route('sports', ['league' => 'serie-a']) }}" class="px-4 py-2 @if($league === 'serie-a') bg-blue-600 text-white @else bg-gray-800 hover:bg-gray-700 @endif rounded-full text-sm transition">
                Serie A
            </a>
            <a href="{{ route('sports', ['league' => 'bundesliga']) }}" class="px-4 py-2 @if($league === 'bundesliga') bg-blue-600 text-white @else bg-gray-800 hover:bg-gray-700 @endif rounded-full text-sm transition">
                Bundesliga
            </a>
            <a href="{{ route('sports', ['league' => 'ligue-1']) }}" class="px-4 py-2 @if($league === 'ligue-1') bg-blue-600 text-white @else bg-gray-800 hover:bg-gray-700 @endif rounded-full text-sm transition">
                Ligue 1
            </a>
            <a href="{{ route('sports', ['league' => 'uefa']) }}" class="px-4 py-2 @if($league === 'uefa') bg-blue-600 text-white @else bg-gray-800 hover:bg-gray-700 @endif rounded-full text-sm transition">
                <i class="fas fa-trophy mr-1"></i> UEFA
            </a>
        </div>

        <div class="flex flex-wrap gap-2 mb-8 items-center">
            <form method="GET" action="{{ route('sports') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ $search ?? '' }}"
                    placeholder="Search sports channels..."
                    class="bg-gray-800 border border-gray-700 rounded-full px-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 w-48">
                <button type="submit" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            @foreach($countriesList as $c)
            <a href="{{ route('sports', ['country' => $c->code]) }}"
                class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 rounded-full text-xs transition">
                <span class="fi fi-{{ $c->code }} mr-1"></span> {{ $c->name }}
            </a>
            @endforeach
        </div>

        @if($channels->isEmpty())
        <div class="text-center py-20">
            <i class="fas fa-futbol text-6xl text-gray-700 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">No Sports Channels Yet</h2>
            <p class="text-gray-500 mb-6">Run <code class="text-blue-400">php artisan iptv:sync-soccer</code> to discover available streams.</p>
            <a href="{{ route('sports') }}" class="text-blue-400 hover:underline text-sm">Clear filters</a>
        </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            @foreach($channels as $channel)
            <div class="relative group">
                <x-channel-card :channel="$channel" />
                @if($channel->source === 'curated')
                <span class="absolute top-2 left-2 px-2 py-0.5 bg-yellow-500/20 text-yellow-400 text-xs rounded-full font-medium">
                    ⭐ Featured
                </span>
                @endif
                @if($channel->is_online && $channel->last_checked_at)
                <span class="absolute top-2 right-2 px-1.5 py-0.5 bg-green-500/20 text-green-400 text-xs rounded-full">
                    ✓
                </span>
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $channels->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

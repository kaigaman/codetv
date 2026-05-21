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
                <h1 class="text-3xl font-bold">Sports Channels</h1>
                <p class="text-gray-400 mt-1">
                    Live sports from around the world —
                    <strong class="text-blue-400">{{ $channels->count() }}</strong> channels
                    <span class="text-gray-600">| <strong>{{ $online }}</strong> verified working</span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('sports') }}" class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm">All Sports</a>
            <a href="{{ route('sports', ['football' => 1]) }}" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition">
                <i class="fas fa-futbol mr-1"></i> Football
            </a>
            @foreach($countriesList as $c)
            <a href="{{ route('sports', ['country' => $c->code]) }}"
                class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition">
                {{ $c->name }}
            </a>
            @endforeach
        </div>

        @if($channels->isEmpty())
        <div class="text-center py-20">
            <i class="fas fa-futbol text-6xl text-gray-700 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">No Sports Channels Yet</h2>
            <p class="text-gray-500 mb-6">Run sports channel sync to discover available streams.</p>
        </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            @foreach($channels as $channel)
            <x-channel-card :channel="$channel" />
            @endforeach
        </div>
    </div>
</div>
@endsection

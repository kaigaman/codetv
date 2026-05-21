@extends('layouts.app')

@section('title', 'Ugandan Channels — CODETV')

@section('content')
<div class="bg-gradient-to-b from-yellow-900/10 via-gray-950 to-gray-950">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex items-center gap-4 mb-8">
            <span class="text-5xl">🇺🇬</span>
            <div>
                <h1 class="text-3xl font-bold">Ugandan Channels</h1>
                <p class="text-gray-400 mt-1">
                    Free live TV channels from Uganda —
                    <strong class="text-codetv-400">{{ $channels->count() }}</strong> channels available
                </p>
            </div>
        </div>

        <div class="mb-8">
            <div class="bg-yellow-900/10 border border-yellow-700/20 rounded-xl p-4 text-sm text-gray-400">
                <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                These channels are sourced from publicly available IPTV streams.
                Streams may be geo-restricted or offline intermittently.
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('uganda') }}" class="px-4 py-2 bg-codetv-600 text-white rounded-full text-sm">All</a>
            @foreach($categories as $catName => $catChannels)
            <a href="#{{ Str::slug($catName) }}"
                class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition">
                {{ $catName }} ({{ $catChannels->count() }})
            </a>
            @endforeach
        </div>

        @foreach($categories as $catName => $catChannels)
        <div class="mb-10" id="{{ Str::slug($catName) }}">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-folder-open text-codetv-400 text-sm"></i>
                {{ $catName }}
                <span class="text-sm font-normal text-gray-500">({{ $catChannels->count() }})</span>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                @foreach($catChannels as $channel)
                <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-gray-900/50 border border-gray-800 rounded-2xl p-6">
        <h3 class="text-lg font-bold mb-2">M3U Playlist</h3>
        <p class="text-gray-400 text-sm mb-4">Use this URL in VLC, IPTV Smarters, or any M3U player:</p>
        <div class="flex items-center gap-2">
            <code class="flex-1 bg-gray-950 text-codetv-300 px-4 py-3 rounded-lg text-sm break-all select-all">
                {{ url('/m3u/uganda.m3u8') }}
            </code>
            <button onclick="navigator.clipboard.writeText('{{ url('/m3u/uganda.m3u8') }}')"
                class="px-4 py-3 bg-codetv-600 hover:bg-codetv-500 rounded-lg text-sm font-medium transition">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </div>
</div>
@endsection

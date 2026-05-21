@extends('layouts.app')

@section('title', 'Verified Working Uganda Channels — CODETV')

@section('content')
<div class="bg-gradient-to-b from-green-900/10 via-gray-950 to-gray-950">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex items-center gap-4 mb-8">
            <span class="text-5xl flex items-center justify-center w-12 h-12 bg-green-900/30 rounded-full">
                <i class="fas fa-check-circle text-green-400 text-2xl"></i>
            </span>
            <div>
                <h1 class="text-3xl font-bold">Verified Working Channels</h1>
                <p class="text-gray-400 mt-1">
                    Only channels that passed stream validation —
                    <strong class="text-green-400">{{ $channels->count() }}</strong> verified working
                    <span class="text-gray-600">(out of {{ $total }} total)</span>
                </p>
            </div>
        </div>

        <div class="mb-8">
            <div class="bg-green-900/10 border border-green-700/20 rounded-xl p-4 text-sm text-gray-400">
                <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                These channels have been verified by our stream validator.
                Each stream was probed and confirmed reachable.
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('uganda.working') }}" class="px-4 py-2 bg-green-600 text-white rounded-full text-sm">Verified Only</a>
            <a href="{{ route('uganda') }}" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition">All Uganda ({{ $total }})</a>
            @foreach($categories as $catName => $catChannels)
            <a href="#{{ Str::slug($catName) }}"
                class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition">
                {{ $catName }} ({{ $catChannels->count() }})
            </a>
            @endforeach
            <a href="{{ route('home') }}" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition ml-auto">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($channels->isEmpty())
        <div class="text-center py-20">
            <i class="fas fa-satellite-dish text-6xl text-gray-700 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">No Verified Channels Yet</h2>
            <p class="text-gray-500 mb-6">Run stream validation to discover working channels.</p>
            <a href="{{ route('uganda') }}" class="px-6 py-3 bg-codetv-600 hover:bg-codetv-500 rounded-xl transition">
                View All Uganda Channels
            </a>
        </div>
        @endif

        @foreach($categories as $catName => $catChannels)
        <div class="mb-10" id="{{ Str::slug($catName) }}">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500 text-sm"></i>
                {{ $catName }}
                <span class="text-sm font-normal text-gray-500">({{ $catChannels->count() }} verified)</span>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                @foreach($catChannels as $channel)
                <a href="{{ route('watch', $channel->slug) }}"
                    class="group bg-gray-800/50 hover:bg-gray-800 rounded-xl p-3 transition border border-green-900/30 hover:border-green-700">
                    <div class="aspect-video bg-gray-900 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                        @if($channel->logo_url)
                        <img src="{{ $channel->logo_url }}" alt="{{ $channel->name }}" class="w-full h-full object-contain p-2">
                        @else
                        <i class="fas fa-tv text-2xl text-gray-600"></i>
                        @endif
                    </div>
                    <h3 class="text-sm font-medium truncate group-hover:text-green-400 transition">{{ $channel->name }}</h3>
                    <div class="flex items-center gap-1 mt-1">
                        <span class="text-xs bg-green-900/50 text-green-400 px-1.5 rounded-full flex items-center gap-1">
                            <i class="fas fa-check-circle text-[10px]"></i> Verified
                        </span>
                        @if($channel->is_hd)<span class="text-xs bg-codetv-900 text-codetv-300 px-1 rounded ml-1">HD</span>@endif
                        @if($channel->latency_ms)
                        <span class="text-xs text-gray-600 ml-1">{{ round($channel->latency_ms) }}ms</span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-gray-900/50 border border-gray-800 rounded-2xl p-6">
        <h3 class="text-lg font-bold mb-2">Verified M3U Playlist</h3>
        <p class="text-gray-400 text-sm mb-4">Only verified working channels — import into VLC, IPTV Smarters, etc.:</p>
        <div class="flex items-center gap-2">
            <code class="flex-1 bg-gray-950 text-green-300 px-4 py-3 rounded-lg text-sm break-all select-all">
                {{ url('/m3u/uganda-verified.m3u8') }}
            </code>
            <button onclick="navigator.clipboard.writeText('{{ url('/m3u/uganda-verified.m3u8') }}')"
                class="px-4 py-3 bg-green-600 hover:bg-green-500 rounded-lg text-sm font-medium transition">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </div>
</div>
@endsection

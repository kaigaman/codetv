@extends('layouts.app')

@section('title', 'World Cup 2026 — Mamboleo TV')

@push('head')
<meta name="description" content="Watch FIFA World Cup 2026 matches live free on Mamboleo TV">
<meta property="og:title" content="World Cup 2026 — Mamboleo TV">
<meta property="og:description" content="Watch FIFA World Cup 2026 live free">
@endpush

@section('content')
<div class="bg-gradient-to-b from-blue-900/10 via-gray-950 to-gray-950">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex items-center gap-4 mb-8">
            <span class="text-5xl flex items-center justify-center w-12 h-12 bg-yellow-500/20 rounded-full">
                <i class="fas fa-trophy text-yellow-400 text-2xl"></i>
            </span>
            <div>
                <h1 class="text-3xl font-bold">FIFA World Cup 2026</h1>
                <p class="text-gray-400 mt-1">
                    Live matches, streams & broadcasters —
                    <strong class="text-yellow-400">{{ $matches ? count($matches) : 0 }}</strong> matches found
                </p>
            </div>
        </div>

        @if(count($matches) > 0)
        <div class="mb-10">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-play-circle text-green-400"></i> Live & Upcoming Matches
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($matches as $match)
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-4 hover:border-yellow-600 transition group">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-sm leading-tight">{{ $match['name'] ?? 'World Cup Match' }}</h3>
                        @if(!empty($match['stream_url']))
                        <a href="{{ $match['stream_url'] }}" target="_blank"
                           class="px-3 py-1 bg-green-600 hover:bg-green-500 text-white text-xs rounded-full transition flex-shrink-0 ml-2">
                            <i class="fas fa-play mr-1"></i> Watch
                        </a>
                        @elseif(!empty($match['iframe_url']))
                        <a href="{{ $match['iframe_url'] }}" target="_blank"
                           class="px-3 py-1 bg-green-600 hover:bg-green-500 text-white text-xs rounded-full transition flex-shrink-0 ml-2">
                            <i class="fas fa-play mr-1"></i> Watch
                        </a>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        @if(!empty($match['country']))
                        <span class="px-2 py-0.5 bg-gray-700 rounded-full">{{ $match['country'] }}</span>
                        @endif
                        <span>{{ $match['source'] ?? 'stream' }}</span>
                    </div>
                    @if(!empty($match['stream_url']))
                    <div class="mt-2 text-xs text-gray-600 truncate">
                        <code class="text-gray-600 select-all">{{ \Illuminate\Support\Str::limit($match['stream_url'], 60) }}</code>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="text-center py-16 mb-10">
            <i class="fas fa-trophy text-6xl text-gray-700 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">World Cup Matches Loading</h2>
            <p class="text-gray-500 mb-4">Match streams will appear here as they become available.</p>
            <a href="{{ route('sports') }}" class="text-blue-400 hover:underline text-sm">
                <i class="fas fa-futbol mr-1"></i> Browse Sports Channels
            </a>
        </div>
        @endif

        @if(count($broadcasters) > 0)
        <div class="mb-10">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-globe text-blue-400"></i> Official Broadcasters by Country
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($broadcasters as $bc)
                <div class="bg-gray-800/30 border border-gray-800 rounded-xl p-3 hover:border-blue-700 transition">
                    <div class="flex items-center gap-2 mb-1">
                        @if(!empty($bc['country']))
                        <span class="text-lg font-bold text-gray-500">{{ $bc['country'] }}</span>
                        @endif
                        <span class="text-xs px-1.5 py-0.5 rounded-full
                            @if($bc['type'] === 'free') bg-green-500/20 text-green-400
                            @else bg-yellow-500/20 text-yellow-400 @endif">
                            {{ $bc['type'] }}
                        </span>
                    </div>
                    <h3 class="text-sm font-medium">{{ $bc['name'] }}</h3>
                    @if(!empty($bc['url']))
                    <a href="{{ $bc['url'] }}" target="_blank"
                       class="text-xs text-blue-400 hover:text-blue-300 mt-1 inline-block">
                        <i class="fas fa-external-link-alt mr-1"></i> Visit
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div>
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-futbol text-blue-400"></i> More Sports Channels
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                @forelse($sportsChannels as $channel)
                <div class="relative group">
                    <x-channel-card :channel="$channel" />
                    @if($channel->is_online)
                    <span class="absolute top-2 right-2 px-1.5 py-0.5 bg-green-500/20 text-green-400 text-xs rounded-full">✓</span>
                    @endif
                </div>
                @empty
                <p class="text-gray-500 text-sm col-span-full">No sports channels available.</p>
                @endforelse
            </div>
            <div class="mt-6 text-center">
                <a href="{{ route('sports') }}" class="inline-block px-6 py-2 bg-blue-600 hover:bg-blue-500 rounded-full text-sm transition">
                    <i class="fas fa-futbol mr-1"></i> All Sports Channels
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

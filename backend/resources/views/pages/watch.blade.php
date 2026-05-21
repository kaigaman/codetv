@extends('layouts.app')
@section('title', $channel->name . ' — CODETV')
@push('head')
<meta name="description" content="Watch {{ $channel->name }} live free on CODETV">
<meta property="og:title" content="{{ $channel->name }} — CODETV">
<meta property="og:description" content="Watch {{ $channel->name }} live free">
@if($channel->logo_url)<meta property="og:image" content="{{ $channel->logo_url }}">@endif
@endpush
@section('content')
<div x-data="{
    playing: false, error: false,
    showInfo: false,
    favorited: false,
    init() {
        this.initPlayer();
        this.checkFav();
    },
    initPlayer() {
        if (!Hls.isSupported()) return;
        let video = document.getElementById('player');
        let hls = new Hls({ enableWorker: true, lowLatencyMode: true });
        hls.loadSource('{{ $channel->stream_url }}');
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => { this.playing = true; this.error = false; video.play().catch(() => {}); });
        hls.on(Hls.Events.ERROR, (event, data) => { if (data.fatal) { this.error = true; this.playing = false; } });
        window.hls = hls;
    },
    async checkFav() {
        let token = localStorage.getItem('codetv_token');
        if (!token) return;
        let resp = await fetch('/api/favorites/check', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ channel_id: {{ $channel->id }} })
        });
        if (resp.ok) { let data = await resp.json(); this.favorited = data.favorited; }
    },
    async toggleFav() {
        let token = localStorage.getItem('codetv_token');
        if (!token) { window.dispatchEvent(new CustomEvent('open-auth')); return; }
        let resp = await fetch('/api/favorites/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ channel_id: {{ $channel->id }} })
        });
        if (resp.ok) { let data = await resp.json(); this.favorited = data.favorited; }
    }
}">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Video Player -->
            <div class="lg:col-span-2">
                <div class="bg-black rounded-2xl overflow-hidden shadow-2xl">
                    <div class="relative aspect-video bg-gray-900">
                        <video id="player" class="w-full h-full" controls autoplay playsinline></video>
                        <div x-show="!playing && !error" class="absolute inset-0 flex items-center justify-center bg-gray-900/80">
                            <i class="fas fa-spinner fa-spin text-4xl text-codetv-400"></i>
                        </div>
                        <div x-show="error" class="absolute inset-0 flex items-center justify-center bg-gray-900/80">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-2"></i>
                                <p class="text-gray-400">Stream unavailable. Try another channel.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Channel Info -->
                <div class="mt-4 flex items-start gap-4">
                    @if($channel->logo_url) <img src="{{ $channel->logo_url }}" class="w-16 h-16 rounded-xl object-contain bg-gray-800 p-2" alt=""> @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold">{{ $channel->name }}</h1>
                            <button @click="toggleFav" :class="favorited ? 'text-red-500' : 'text-gray-600 hover:text-red-400'" class="transition text-xl" title="Toggle favorite">
                                <i :class="favorited ? 'fas fa-heart' : 'far fa-heart'"></i>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-400">
                            @if($channel->country)<span><i class="fas fa-map-marker-alt mr-1 text-codetv-400"></i>{{ $channel->country->name }}</span>@endif
                            @if($channel->category)<span class="px-2 py-0.5 bg-gray-800 rounded-full"><i class="fas fa-tag mr-1 text-codetv-400"></i>{{ $channel->category->name }}</span>@endif
                            @if($channel->is_hd)<span class="px-2 py-0.5 bg-codetv-900 text-codetv-300 rounded-full"><i class="fas fa-hdmi"></i> HD</span>@endif
                            @if($channel->resolution)<span class="text-xs bg-gray-800 px-2 py-0.5 rounded">{{ $channel->resolution }}</span>@endif
                        </div>
                    </div>
                    <a href="{{ route('browse') }}" class="text-sm text-codetv-400 hover:text-codetv-300 whitespace-nowrap"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                </div>
                <!-- Channel Details -->
                <div class="mt-4 bg-gray-800/30 border border-gray-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-300"><i class="fas fa-info-circle mr-2 text-codetv-400"></i>Channel Details</h3>
                        <button @click="showInfo = !showInfo" class="text-xs text-gray-500 hover:text-gray-300 transition">
                            <i class="fas" :class="showInfo ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>
                    <div x-show="showInfo" class="space-y-2 text-sm text-gray-400">
                        @if($channel->description)<p>{{ $channel->description }}</p>@endif
                        <div class="grid grid-cols-2 gap-2">
                            @if($channel->stream_url)<div><span class="text-gray-500">Stream:</span><br><code class="text-xs text-codetv-300 break-all select-all">{{ $channel->stream_url }}</code></div>@endif
                            @if($channel->website)<div><span class="text-gray-500">Website:</span><br><a href="{{ $channel->website }}" target="_blank" class="text-codetv-400 hover:text-codetv-300 text-xs">{{ $channel->website }}</a></div>@endif
                            @if($channel->tvg_id)<div><span class="text-gray-500">TVG ID:</span><br><span class="text-xs">{{ $channel->tvg_id }}</span></div>@endif
                            @if($channel->languages->isNotEmpty())<div><span class="text-gray-500">Languages:</span><br><span class="text-xs">{{ $channel->languages->pluck('name')->implode(', ') }}</span></div>@endif
                            @if($channel->stream_type)<div><span class="text-gray-500">Type:</span><br><span class="text-xs">{{ $channel->stream_type }}</span></div>@endif
                        </div>
                        <div class="flex gap-2 mt-2">
                            <a href="{{ $channel->stream_url }}" target="_blank" class="px-3 py-1.5 bg-codetv-900/50 text-codetv-300 rounded-lg text-xs hover:bg-codetv-800 transition"><i class="fas fa-external-link-alt mr-1"></i> Open Stream</a>
                            @if($channel->website)<a href="{{ $channel->website }}" target="_blank" class="px-3 py-1.5 bg-gray-800 text-gray-400 rounded-lg text-xs hover:bg-gray-700 transition"><i class="fas fa-globe mr-1"></i> Website</a>@endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- Related Channels Sidebar -->
            <div class="lg:col-span-1">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-tv text-codetv-400"></i> Related Channels
                    @if($channel->country) <span class="text-sm font-normal text-gray-500">from {{ $channel->country->name }}</span> @endif
                </h3>
                <div class="space-y-2 max-h-[600px] overflow-y-auto pr-2">
                    @forelse($related as $rel)
                    <a href="{{ route('watch', $rel->slug) }}" class="flex items-center gap-3 bg-gray-800/50 hover:bg-gray-800 rounded-xl p-3 transition border border-gray-800 hover:border-codetv-700 group">
                        @if($rel->logo_url) <img src="{{ $rel->logo_url }}" class="w-10 h-10 rounded-lg object-contain bg-gray-900 p-1" alt="">
                        @else <div class="w-10 h-10 rounded-lg bg-gray-900 flex items-center justify-center"><i class="fas fa-tv text-gray-600"></i></div> @endif
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate group-hover:text-codetv-400 transition">{{ $rel->name }}</div>
                            @if($rel->category) <div class="text-xs text-gray-500">{{ $rel->category->name }}</div> @endif
                        </div>
                    </a>
                    @empty <p class="text-gray-500 text-sm">No related channels found.</p> @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

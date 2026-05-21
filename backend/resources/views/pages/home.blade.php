@extends('layouts.app')
@section('title', 'CODETV — Free IPTV Channels Worldwide')
@section('content')
<div x-data="{
    search: '', results: [], searching: false,
    favorites: new Set(),
    async doSearch() {
        if (this.search.length < 2) { this.results = []; return; }
        this.searching = true;
        let resp = await fetch('/api/search?q=' + encodeURIComponent(this.search));
        this.results = await resp.json();
        this.searching = false;
    },
    async toggleFav(channelId) {
        let token = localStorage.getItem('codetv_token');
        if (!token) { window.dispatchEvent(new CustomEvent('open-auth')); return; }
        let resp = await fetch('/api/favorites/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ channel_id: channelId })
        });
        let data = await resp.json();
        if (data.favorited) this.favorites.add(channelId);
        else this.favorites.delete(channelId);
    }
}">
    <div class="relative bg-gradient-to-b from-gray-900 via-gray-950 to-gray-950">
        <div class="max-w-7xl mx-auto px-4 py-16 md:py-24">
            <div class="text-center max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 bg-codetv-900/30 border border-codetv-700/30 rounded-full px-4 py-1.5 mb-6">
                    <span class="w-2 h-2 bg-codetv-400 rounded-full animate-pulse"></span>
                    <span class="text-codetv-300 text-sm font-medium">Uganda-first: {{ $ugandaChannels->count() }} local channels</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-4">
                    Free IPTV
                    <span class="bg-gradient-to-r from-codetv-400 to-blue-500 bg-clip-text text-transparent">Channels</span>
                    <br>From Everywhere
                </h1>
                <p class="text-gray-400 text-lg mb-8">
                    Watch <strong class="text-codetv-400">{{ number_format($stats['online']) }}</strong> live channels online right now.
                    Auto-detected for your region.
                </p>

                <div class="relative max-w-xl mx-auto">
                    <input type="text" x-model="search" @input.debounce="doSearch"
                        placeholder="Search channels, countries..."
                        class="w-full bg-gray-800 border border-gray-700 rounded-xl px-5 py-3.5 pl-12 text-white placeholder-gray-500 focus:outline-none focus:border-codetv-500 focus:ring-1 focus:ring-codetv-500 transition">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    <div x-show="results.length > 0 || searching" x-cloak
                        class="absolute top-full mt-2 w-full bg-gray-800 border border-gray-700 rounded-xl overflow-hidden shadow-2xl z-50">
                        <template x-if="searching">
                            <div class="p-4 text-center text-gray-400"><i class="fas fa-spinner fa-spin"></i> Searching...</div>
                        </template>
                        <template x-for="ch in results.channels" :key="ch.id">
                            <a :href="'/watch/' + ch.slug"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-gray-700/50 transition border-b border-gray-700/50 last:border-0">
                                <img :src="ch.logo_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(ch.name || 'TV') + '&size=40&background=random&color=fff&bold=true'" class="w-8 h-8 rounded object-cover bg-gray-900" alt="">
                                <div class="text-left">
                                    <div class="text-sm font-medium" x-text="ch.name"></div>
                                    <div class="text-xs text-gray-500" x-text="ch.country?.name || ''"></div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($ugandaChannels->isNotEmpty())
    <div class="max-w-7xl mx-auto px-4 -mt-8 mb-12">
        <div class="bg-gradient-to-r from-yellow-900/20 to-codetv-900/20 border border-yellow-700/20 rounded-2xl p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <span>🇺🇬</span> Ugandan Channels
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Free local channels from Uganda</p>
                </div>
                <a href="{{ route('uganda') }}" class="text-codetv-400 hover:text-codetv-300 text-sm font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($ugandaChannels as $channel)
                <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if(isset($internationalChannels) && $internationalChannels->isNotEmpty())
    <div class="max-w-7xl mx-auto px-4 mb-12">
        <div class="bg-gradient-to-r from-blue-900/20 to-purple-900/20 border border-blue-700/20 rounded-2xl p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <span>🌍</span> International Channels
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Live channels from around the world</p>
                </div>
                <a href="{{ route('international') }}" class="text-codetv-400 hover:text-codetv-300 text-sm font-medium">
                    Browse All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($internationalChannels as $channel)
                <x-channel-card :channel="$channel" />
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 mb-12">
        <div class="flex items-center gap-8 mb-6">
            <h2 class="text-xl font-bold">Browse by Country</h2>
            <a href="{{ route('browse') }}" class="text-sm text-gray-500 hover:text-codetv-400 ml-auto">View all countries <i class="fas fa-arrow-right ml-1"></i></a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($countries as $country)
            <a href="{{ route('browse', ['country' => $country->code]) }}"
                class="flex items-center gap-3 bg-gray-800/50 hover:bg-gray-800 rounded-xl p-3 transition border border-gray-800 hover:border-codetv-700 group">
                <span class="text-2xl">@if($country->flag_url)<img src="{{ $country->flag_url }}" class="w-6 h-4" alt="{{ $country->name }}">@else🏴@endif</span>
                <div class="min-w-0">
                    <div class="text-sm font-medium truncate group-hover:text-codetv-400 transition">{{ $country->name }}</div>
                    <div class="text-xs text-gray-500">{{ $country->channels_count ?? 0 }} channels</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mb-12">
        <h2 class="text-xl font-bold mb-6">Categories</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($categories as $category)
            <a href="{{ route('browse', ['category' => $category->slug]) }}"
                class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-full text-sm transition border border-gray-700 hover:border-codetv-600">
                {{ $category->name }}
                <span class="text-gray-500 ml-1">({{ $category->channels_count }})</span>
            </a>
            @endforeach
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mb-16">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold">HD Channels</h2>
            <a href="{{ route('browse') }}?hd=1" class="text-sm text-gray-500 hover:text-codetv-400">View all</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($featured as $channel)
            <div class="relative">
                <x-channel-card :channel="$channel" />
                <button @click="toggleFav({{ $channel->id }})" class="absolute top-2 right-2 w-7 h-7 bg-gray-900/80 hover:bg-red-900/60 rounded-full flex items-center justify-center text-gray-500 hover:text-red-400 transition opacity-0 group-hover:opacity-100 z-10" title="Add to favorites">
                    <i class="fas fa-heart text-xs"></i>
                </button>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>
@endsection

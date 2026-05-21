@extends('layouts.app')

@section('title', 'Browse Channels — CODETV')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8"
    x-data="{
        country: '{{ $countryCode }}',
        category: '{{ $categorySlug ?? '' }}',
        search: '{{ $search ?? '' }}',
        channels: @json($channels->items()),
        loading: false,
        async filter() {
            let params = new URLSearchParams();
            if (this.country) params.set('country', this.country);
            if (this.category) params.set('category', this.category);
            if (this.search) params.set('search', this.search);
            window.location = '/browse?' + params.toString();
        },
        async toggleFav(channelId) {
            let token = localStorage.getItem('codetv_token');
            if (!token) { window.dispatchEvent(new CustomEvent('open-auth')); return; }
            let resp = await fetch('/api/favorites/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                body: JSON.stringify({ channel_id: channelId })
            });
        }
    }">
    <div class="flex flex-col md:flex-row gap-8">
        <div class="md:w-64 shrink-0">
            <h1 class="text-2xl font-bold mb-6">Browse Channels</h1>

            <div class="space-y-6">
                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Search</label>
                    <input type="text" x-model="search" @input.debounce="filter"
                        placeholder="Channel name..."
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-codetv-500">
                </div>

                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Country</label>
                    <select x-model="country" @change="filter"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:outline-none focus:border-codetv-500">
                        <option value="">All Countries</option>
                        <option value="ug">🇺🇬 Uganda</option>
                        @foreach($countries as $c)
                        <option value="{{ $c->code }}">{{ $c->name }} ({{ $c->channels_count }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Category</label>
                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        <a href="{{ route('browse', ['country' => $countryCode]) }}"
                            class="block px-3 py-2 rounded-lg text-sm {{ !$categorySlug ? 'bg-codetv-900/50 text-codetv-300 border border-codetv-700/30' : 'text-gray-400 hover:bg-gray-800' }}">
                            All Categories
                        </a>
                        @foreach($categories as $cat)
                        <a href="{{ route('browse', ['country' => $countryCode, 'category' => $cat->slug]) }}"
                            class="block px-3 py-2 rounded-lg text-sm {{ $categorySlug === $cat->slug ? 'bg-codetv-900/50 text-codetv-300 border border-codetv-700/30' : 'text-gray-400 hover:bg-gray-800' }}">
                            {{ $cat->name }} ({{ $cat->channels_count }})
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500">
                    Showing {{ $channels->firstItem() ?? 0 }} - {{ $channels->lastItem() ?? 0 }} of {{ $channels->total() }} channels
                </p>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">
                        <i class="fas fa-circle text-green-500 text-[8px] mr-1"></i> Online only
                    </span>
                </div>
            </div>

            @if($channels->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-tv text-5xl text-gray-700 mb-4"></i>
                <p class="text-gray-500">No channels found matching your filters.</p>
                <a href="{{ route('browse') }}" class="text-codetv-400 hover:text-codetv-300 text-sm mt-2 inline-block">Clear filters</a>
            </div>
            @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                @foreach($channels as $channel)
                <div class="relative">
                    <x-channel-card :channel="$channel" />
                    <button @click="toggleFav({{ $channel->id }})" class="absolute top-2 right-2 w-7 h-7 bg-gray-900/80 hover:bg-red-900/60 rounded-full flex items-center justify-center text-gray-500 hover:text-red-400 transition opacity-0 group-hover:opacity-100 z-10" title="Add to favorites">
                        <i class="fas fa-heart text-xs"></i>
                    </button>
                </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-center">
                {{ $channels->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    nav[aria-label="Pagination"] a, nav[aria-label="Pagination"] span {
        @apply px-3 py-2 rounded-lg text-sm;
    }
    nav[aria-label="Pagination"] a {
        @apply bg-gray-800 text-gray-300 hover:bg-gray-700;
    }
    nav[aria-label="Pagination"] span[aria-current="page"] {
        @apply bg-codetv-600 text-white;
    }
</style>
@endsection

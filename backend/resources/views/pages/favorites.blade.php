@extends('layouts.app')
@section('title', 'My Favorites — CODETV')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-10"
    x-data="{
        channels: [],
        loading: true,
        token: localStorage.getItem('codetv_token'),
        init() {
            if (!this.token) { this.loading = false; return; }
            this.loadFavorites();
        },
        async loadFavorites() {
            this.loading = true;
            try {
                let resp = await fetch('/api/favorites', {
                    headers: { 'Authorization': 'Bearer ' + this.token }
                });
                if (resp.ok) this.channels = await resp.json();
            } catch(e) {}
            this.loading = false;
        },
        async remove(id) {
            let resp = await fetch('/api/favorites/toggle', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + this.token,
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                },
                body: JSON.stringify({ channel_id: id })
            });
            if (resp.ok) this.channels = this.channels.filter(c => c.id !== id);
        }
    }">
    <div class="flex items-center gap-3 mb-8">
        <i class="fas fa-heart text-3xl text-red-500"></i>
        <div>
            <h1 class="text-3xl font-bold">My Favorites</h1>
            <p class="text-gray-400 mt-1">Your saved channels</p>
        </div>
    </div>

    <template x-if="!token">
        <div class="text-center py-16 bg-gray-800/30 rounded-2xl border border-gray-800">
            <i class="fas fa-user-lock text-5xl text-gray-700 mb-4"></i>
            <p class="text-gray-500 mb-4">Sign in to save your favorite channels.</p>
            <button @click="$dispatch('open-auth')" class="px-6 py-3 bg-codetv-600 hover:bg-codetv-500 rounded-xl text-sm font-medium transition">Sign In</button>
        </div>
    </template>

    <template x-if="token && loading">
        <div class="text-center py-16"><i class="fas fa-spinner fa-spin text-4xl text-codetv-400"></i></div>
    </template>

    <template x-if="token && !loading && channels.length === 0">
        <div class="text-center py-16 bg-gray-800/30 rounded-2xl border border-gray-800">
            <i class="fas fa-heart-broken text-5xl text-gray-700 mb-4"></i>
            <p class="text-gray-500">No favorites yet. Browse channels and add your favorites!</p>
            <a href="{{ route('browse') }}" class="text-codetv-400 hover:text-codetv-300 text-sm mt-2 inline-block">Browse Channels</a>
        </div>
    </template>

    <template x-if="token && !loading && channels.length > 0">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <template x-for="ch in channels" :key="ch.id">
                <div class="group bg-gray-800/50 hover:bg-gray-800 rounded-xl p-3 transition border border-gray-800 hover:border-codetv-700 relative">
                    <a :href="'/watch/' + ch.slug">
                        <div class="aspect-video bg-gray-900 rounded-lg mb-2 flex items-center justify-center overflow-hidden relative">
                            <template x-if="ch.logo_url">
                                <img :src="ch.logo_url" class="w-full h-full object-cover" alt="">
                            </template>
                            <template x-if="!ch.logo_url">
                                <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(ch.name || 'TV') + '&size=320&background=random&color=fff&bold=true'" class="w-full h-full object-cover" alt="">
                            </template>
                            <span class="absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full animate-pulse ring-2 ring-black/50"></span>
                        </div>
                        <h3 class="text-sm font-medium truncate group-hover:text-codetv-400 transition" x-text="ch.name"></h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500 truncate flex items-center gap-1" x-show="ch.country">
                                <span class="inline-block w-4 h-3 rounded-sm bg-cover" :class="'fi fi-' + ch.country.code.toLowerCase()"></span>
                                <span x-text="ch.country.name"></span>
                            </span>
                            <template x-if="ch.category">
                                <span class="text-xs text-gray-600 bg-gray-700/50 px-1.5 py-0.5 rounded" x-text="ch.category.name"></span>
                            </template>
                            <template x-if="ch.is_hd">
                                <span class="text-xs bg-codetv-900/80 text-codetv-300 px-1.5 py-0.5 rounded font-medium">HD</span>
                            </template>
                        </div>
                    </a>
                    <button @click="remove(ch.id)" class="absolute top-2 right-2 w-7 h-7 bg-red-900/50 hover:bg-red-700 rounded-full flex items-center justify-center text-red-300 text-xs transition z-10" title="Remove from favorites">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
                            <template x-if="!ch.logo_url">
                                <i class="fas fa-tv text-2xl text-gray-600"></i>
                            </template>
                        </div>
                        <h3 class="text-sm font-medium truncate group-hover:text-codetv-400 transition" x-text="ch.name"></h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500 truncate" x-text="ch.country?.name || ''"></span>
                            <template x-if="ch.is_hd">
                                <span class="text-xs bg-codetv-900 text-codetv-300 px-1.5 py-0.5 rounded">HD</span>
                            </template>
                        </div>
                    </a>
                    <button @click="remove(ch.id)" class="absolute top-2 right-2 w-7 h-7 bg-red-900/50 hover:bg-red-700 rounded-full flex items-center justify-center text-red-300 text-xs transition" title="Remove from favorites">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>
@endsection

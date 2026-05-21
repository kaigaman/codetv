@extends('layouts.app')
@section('title', 'TV Guide — CODETV')
@section('content')
<div x-data="{
    channels: @json($channels),
    selectedCountry: 'ug',
    selectedCategory: '',
    search: '',
    get filtered() {
        return this.channels.filter(c => {
            if (this.search && !c.name.toLowerCase().includes(this.search.toLowerCase())) return false;
            if (this.selectedCategory && c.category?.slug !== this.selectedCategory) return false;
            return true;
        });
    }
}" class="bg-gradient-to-b from-gray-900 via-gray-950 to-gray-950">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex items-center gap-3 mb-8">
            <i class="fas fa-calendar-alt text-3xl text-codetv-400"></i>
            <div>
                <h1 class="text-3xl font-bold">TV Guide</h1>
                <p class="text-gray-400 mt-1">Browse channels and find what's on — <strong class="text-codetv-400">{{ $channels->count() }}</strong> Uganda channels</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-6">
            <select x-model="selectedCategory" class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm text-white">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <input type="text" x-model="search" placeholder="Search channels..." class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm text-white placeholder-gray-500 flex-1 min-w-[200px]">
            <span class="text-sm text-gray-500 self-center" x-text="filtered.length + ' channels'"></span>
        </div>

        <div class="grid gap-2">
            <template x-for="ch in filtered" :key="ch.id">
                <a :href="'/watch/' + ch.slug" class="flex items-center gap-4 bg-gray-800/50 hover:bg-gray-800 rounded-xl p-3 transition border border-gray-800 hover:border-codetv-700 group">
                    <div class="w-12 h-9 rounded-lg bg-gray-800 flex items-center justify-center overflow-hidden shrink-0">
                        <template x-if="ch.logo_url">
                            <img :src="ch.logo_url" class="w-full h-full object-cover" alt="">
                        </template>
                        <template x-if="!ch.logo_url">
                            <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(ch.name || 'TV') + '&size=64&background=random&color=fff&bold=true'" class="w-full h-full object-cover" alt="">
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium group-hover:text-codetv-400 transition" x-text="ch.name"></div>
                        <div class="text-xs text-gray-500" x-text="ch.category?.name || ''"></div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <span class="text-xs text-green-400">Live</span>
                        <template x-if="ch.is_hd">
                            <span class="text-xs bg-codetv-900 text-codetv-300 px-1.5 py-0.5 rounded">HD</span>
                        </template>
                        <i class="fas fa-chevron-right text-gray-600 text-sm"></i>
                    </div>
                </a>
            </template>
            <div x-show="filtered.length === 0" class="text-center py-12 text-gray-500">
                <i class="fas fa-search text-4xl mb-3"></i>
                <p>No channels match your filters.</p>
            </div>
        </div>
    </div>
</div>
@endsection

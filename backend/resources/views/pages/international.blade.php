@extends('layouts.app')

@section('title', 'International Channels — Mamboleo TV')

@section('content')
<div class="bg-gradient-to-b from-purple-900/10 via-gray-950 to-gray-950">
    <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-8">
            <div class="flex items-center gap-4">
                <span class="flex items-center justify-center w-12 h-12 bg-purple-900/30 rounded-full">
                    <i class="fas fa-globe-americas text-purple-400 text-2xl"></i>
                </span>
                <div>
                    <h1 class="text-3xl font-bold">International Channels</h1>
                    <p class="text-gray-400 mt-1">
                        Live channels from every country —
                        <strong class="text-purple-400">{{ number_format($channels->total()) }}</strong> channels
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <div class="md:w-56 shrink-0 space-y-5">
                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Search</label>
                    <form method="GET" action="{{ route('international') }}" id="filter-form">
                        @if($countryCode)
                        <input type="hidden" name="country" value="{{ $countryCode }}">
                        @endif
                        @if($categorySlug)
                        <input type="hidden" name="category" value="{{ $categorySlug }}">
                        @endif
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Channel name..."
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-purple-500"
                            onchange="this.form.submit()">
                    </form>
                </div>

                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Country</label>
                    <div class="space-y-1 max-h-80 overflow-y-auto">
                        <a href="{{ route('international', array_filter(['category' => $categorySlug, 'search' => $search])) }}"
                            class="block px-3 py-2 rounded-lg text-sm {{ !$countryCode ? 'bg-purple-900/50 text-purple-300 border border-purple-700/30' : 'text-gray-400 hover:bg-gray-800' }}">
                            🌍 All Countries
                        </a>
                        @foreach($countriesList as $c)
                        <a href="{{ route('international', array_filter(['country' => $c->code, 'category' => $categorySlug, 'search' => $search])) }}"
                            class="block px-3 py-2 rounded-lg text-sm {{ $countryCode === $c->code ? 'bg-purple-900/50 text-purple-300 border border-purple-700/30' : 'text-gray-400 hover:bg-gray-800' }}">
                            {{ $c->name }} ({{ $c->channels_count }})
                        </a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Category</label>
                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        <a href="{{ route('international', array_filter(['country' => $countryCode, 'search' => $search])) }}"
                            class="block px-3 py-2 rounded-lg text-sm {{ !$categorySlug ? 'bg-purple-900/50 text-purple-300 border border-purple-700/30' : 'text-gray-400 hover:bg-gray-800' }}">
                            All Categories
                        </a>
                        @foreach($categories as $cat)
                        <a href="{{ route('international', array_filter(['country' => $countryCode, 'category' => $cat->slug, 'search' => $search])) }}"
                            class="block px-3 py-2 rounded-lg text-sm {{ $categorySlug === $cat->slug ? 'bg-purple-900/50 text-purple-300 border border-purple-700/30' : 'text-gray-400 hover:bg-gray-800' }}">
                            {{ $cat->name }} ({{ $cat->channels_count }})
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-500">
                        Showing {{ $channels->firstItem() ?? 0 }} - {{ $channels->lastItem() ?? 0 }} of {{ $channels->total() }} channels
                    </p>
                </div>

                @if($channels->isEmpty())
                <div class="text-center py-20">
                    <i class="fas fa-globe-americas text-6xl text-gray-700 mb-4"></i>
                    <h2 class="text-xl font-bold mb-2">No Channels Found</h2>
                    <p class="text-gray-500 mb-6">{{ $search ? 'No channels match your search.' : 'Run global sync to populate international channels.' }}</p>
                    @if($countryCode || $categorySlug || $search)
                    <a href="{{ route('international') }}" class="text-purple-400 hover:text-purple-300 text-sm">Clear all filters</a>
                    @endif
                </div>
                @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    @foreach($channels as $channel)
                    <x-channel-card :channel="$channel" />
                    @endforeach
                </div>

                <div class="mt-8 flex justify-center">
                    {{ $channels->appends(request()->query())->links() }}
                </div>
                @endif
            </div>
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
        @apply bg-purple-600 text-white;
    }
</style>
@endsection

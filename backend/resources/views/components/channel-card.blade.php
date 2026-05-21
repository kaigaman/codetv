@props(['channel'])

@php
    $thumbnailUrl = $channel->logo_url;
    if (!$thumbnailUrl) {
        $cachePath = "thumbnails/{$channel->id}.svg";
        $disk = Illuminate\Support\Facades\Storage::disk('public');
        if ($disk->exists($cachePath)) {
            $thumbnailUrl = asset("storage/{$cachePath}");
        } else {
            $thumbnailUrl = "https://ui-avatars.com/api/?name=" . urlencode($channel->name ?: 'TV') . "&size=320&background=random&color=fff&bold=true";
        }
    }
@endphp

<a href="{{ route('watch', $channel->slug) }}"
   class="group bg-gray-800/50 hover:bg-gray-800 rounded-xl p-3 transition border border-gray-800 hover:border-codetv-700">
    <div class="aspect-video bg-gray-900 rounded-lg mb-2 flex items-center justify-center overflow-hidden relative">
        <img src="{{ $thumbnailUrl }}"
             alt="{{ $channel->name }}"
             class="w-full h-full object-cover"
             loading="lazy"
             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($channel->name ?: 'TV') }}&size=320&background=random&color=fff&bold=true'">
        @if($channel->is_online)
        <span class="absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full animate-pulse ring-2 ring-black/50"></span>
        @endif
    </div>
    <h3 class="text-sm font-medium truncate group-hover:text-codetv-400 transition">{{ $channel->name }}</h3>
    <div class="flex items-center gap-2 mt-1">
        @if($channel->country)
        <span class="text-xs text-gray-500 truncate flex items-center gap-1">
            <span class="fi fi-{{ $channel->country->code }} inline-block w-4 h-3 rounded-sm bg-cover"></span>
            {{ $channel->country->name }}
        </span>
        @endif
        @if($channel->category)
        <span class="text-xs text-gray-600 bg-gray-700/50 px-1.5 py-0.5 rounded">{{ $channel->category->name }}</span>
        @endif
        @if($channel->is_hd)
        <span class="text-xs bg-codetv-900/80 text-codetv-300 px-1.5 py-0.5 rounded font-medium">HD</span>
        @endif
    </div>
</a>

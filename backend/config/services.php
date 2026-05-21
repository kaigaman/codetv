<?php

return [
    'iptv_org' => [
        'channels' => env('IPTV_ORG_API', 'https://iptv-org.github.io/api/channels.json'),
        'streams' => env('IPTV_ORG_STREAMS', 'https://iptv-org.github.io/api/streams.json'),
    ],
    'free_tv' => [
        'm3u' => env('FREE_TV_M3U', 'https://raw.githubusercontent.com/Free-TV/IPTV/master/playlist.m3u8'),
    ],
    'world_ip_tv' => [
        'playlist' => env('WORLD_IP_TV', 'https://romaxa55.github.io/world_ip_tv/output/index.m3u'),
    ],
    'uganda' => [
        'm3u' => env('UGANDA_M3U', 'https://iptv-org.github.io/iptv/countries/ug.m3u'),
    ],
    'python_api' => [
        'url' => env('PYTHON_API', 'http://python:8000'),
    ],
    'kptv_fast' => [
        'url' => env('KPTV_FAST_API', 'http://kptv-fast:8080'),
    ],
    'iptv_api' => [
        'url' => env('IPTV_API_URL', 'http://iptv-api:8080'),
    ],
    'logos' => [
        'url' => env('IPTV_ORG_LOGOS', 'https://iptv-org.github.io/api/logos.json'),
    ],
];

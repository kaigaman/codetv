EIGHT08_DOMAINS = [
    {"name": "808ball2", "url": "https://808ball2.com", "priority": 1},
    {"name": "score808", "url": "https://www.score808.tv", "priority": 2},
    {"name": "livesports088", "url": "https://www.livesports088.com", "priority": 3},
    {"name": "808fubo", "url": "https://www.808fubo808.com", "priority": 4},
    {"name": "808sbo", "url": "https://www.808sbo.com", "priority": 5},
]

SPORT_PAGES = [
    "/football.html",
    "/basketball.html",
    "/others.html",
    "/live-stream",
    "/",
]

STREAM_SELECTORS = [
    "iframe[src]",
    "video source[src]",
    "video[src]",
    "a[href*='.m3u8']",
    "a[href*='stream']",
    "[data-stream]",
    ".stream-link",
]

MATCH_CONTAINER_SELECTORS = [
    ".match-item",
    ".match-card",
    ".game-item",
    ".event-item",
    "tr.match",
    "li.match",
    "[class*='match']",
    "[class*='game']",
]

from typing import Optional, Callable
from loguru import logger

from m3u_parser import parse_m3u, deduplicate_by_url
from http_client import close_session


SPORTS_SOURCES = [
    {"name": "iptv-org-sports", "url": "https://iptv-org.github.io/iptv/categories/sports.m3u", "type": "m3u"},
    {"name": "iptv-org-football", "url": "https://iptv-org.github.io/iptv/categories/football.m3u", "type": "m3u"},
    {"name": "Free-TV Sports", "url": "https://raw.githubusercontent.com/Free-TV/IPTV/master/playlist.m3u8", "type": "m3u-filtered"},
]

SPORTS_KEYWORDS = [
    "sport", "football", "soccer", "espn", "sky sport", "bt sport",
    "bein sport", "elevensport", "supersport", "dazn", "optus sport",
    "nfl", "nba", "mlb", "nhl", "ufc", "wwe", "f1", "motogp",
    "tennis", "cricket", "rugby", "golf", "boxing", "mma",
    "premier league", "laliga", "serie a", "bundesliga", "ligue 1",
    "uefa", "champions league", "europa league",
    "olympic", "formula", "moto", "nascar",
    "star sport", "sport tv", "canal+ sport",
]


def _is_sports(name: str, group: str = "", tvg_id: str = "", country: str = "") -> bool:
    text = f"{name} {group} {tvg_id}".lower()
    for kw in SPORTS_KEYWORDS:
        if kw in text:
            return True
    return False


class SportsScraper:
    async def get_sports_channels(self) -> list:
        channels = []
        for source in SPORTS_SOURCES:
            try:
                if source["type"] == "m3u":
                    result = await parse_m3u(source["url"], source_name=source["name"])
                elif source["type"] == "m3u-filtered":
                    result = await parse_m3u(source["url"], source_name=source["name"], filter_func=_is_sports)
                else:
                    result = []
                channels.extend(result)
                logger.info(f"Found {len(result)} sports channels from {source['name']}")
            except Exception as e:
                logger.error(f"Failed to process source {source['name']}: {e}")

        result = deduplicate_by_url(channels)
        logger.info(f"Sports scrape complete: {len(result)} unique channels")
        return result

    async def get_football_channels(self) -> list:
        all_channels = await self.get_sports_channels()
        football_kw = ["football", "soccer", "premier league", "laliga", "serie a",
                       "bundesliga", "ligue 1", "champions league", "europa league",
                       "uefa", "fifa", "world cup"]

        football = []
        for ch in all_channels:
            text = f"{ch['name']} {ch.get('category', '')}".lower()
            for kw in football_kw:
                if kw in text:
                    football.append(ch)
                    break

        logger.info(f"Found {len(football)} football-specific channels")
        return football

    async def scrape_all_sources(self) -> list:
        return await self.get_sports_channels()

    async def close(self):
        await close_session()

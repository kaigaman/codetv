import asyncio
import re
from typing import Optional

from loguru import logger

from m3u_parser import parse_m3u, deduplicate_by_url
from http_client import fetch_text, fetch_json, close_session

BEIN_M3U_SOURCES = [
    {"name": "uzayterligi-bein", "url": "https://uzayterligi.lol/static/bs1.m3u8", "type": "direct"},
    {"name": "uzayterligi-bein2", "url": "https://uzayterligi.lol/static/bs2.m3u8", "type": "direct"},
    {"name": "uzayterligi-bein3", "url": "https://uzayterligi.lol/static/bs3.m3u8", "type": "direct"},
    {"name": "uzayterligi-bein4", "url": "https://uzayterligi.lol/static/bs4.m3u8", "type": "direct"},
    {"name": "uzayterligi-bein5", "url": "https://uzayterligi.lol/static/bs5.m3u8", "type": "direct"},
    {"name": "iptv-org-sports", "url": "https://iptv-org.github.io/iptv/categories/sports.m3u", "type": "m3u"},
    {"name": "free-tv", "url": "https://raw.githubusercontent.com/Free-TV/IPTV/master/playlist.m3u8", "type": "m3u"},
    {"name": "world-ip-tv", "url": "https://romaxa55.github.io/world_ip_tv/output/index.m3u", "type": "m3u"},
    {"name": "herbert-he", "url": "https://raw.githubusercontent.com/HerbertHe/iptv-sources/main/iptv.m3u", "type": "m3u"},
]

BEIN_KEYWORDS = [
    "bein sport", "beın sport", "beIN", "beın",
    "bein", "beın", "be in sports",
]

BEIN_CHANNEL_NAMES = [
    "beIN Sports 1", "beIN Sports 2", "beIN Sports 3", "beIN Sports 4",
    "beIN Sports 5", "beIN Sports 6", "beIN Sports 7", "beIN Sports 8",
    "beIN Sports 9", "beIN Sports 10", "beIN Sports 11", "beIN Sports 12",
    "beIN Sports 13", "beIN Sports 14", "beIN Sports 15", "beIN Sports 16",
    "beIN Sports MAX 1", "beIN Sports MAX 2", "beIN Sports MAX 3", "beIN Sports MAX 4",
    "beIN Sports XTRA 1", "beIN Sports XTRA 2",
    "beIN Sports 4K", "beIN Sports UHD 1", "beIN Sports UHD 2", "beIN Sports UHD 3",
    "beIN Sports UHD 4", "beIN Sports UHD 5",
    "beIN Sports News", "beIN Sports NBA HD",
    "beIN Sports English", "beIN Sports France", "beIN Sports España",
    "beIN Series Drama", "beIN Series Vice", "beIN Series Sci-Fi",
    "beIN Movies Comedy", "beIN Movies Premier", "beIN Movies Action", "beIN Movies Turk",
    "beIN Box Office 1", "beIN Box Office 2", "beIN Box Office 3",
    "beIN Premier HD", "beIN Premier 2 HD", "beIN Action HD", "beIN Action 2 HD",
    "beIN Stars HD", "beIN Festival HD", "beIN Family HD",
    "S Sport HD 1", "S Sport HD 2",
    "S Sport+ Plus HD 1", "S Sport+ Plus HD 2", "S Sport+ Plus HD 3",
    "S Sport+ Plus HD 4", "S Sport+ Plus HD 5", "S Sport+ Plus HD 6",
]


def _is_be_in(name: str, group: str = "", tvg_id: str = "", country: str = "") -> bool:
    text = f"{name} {group} {tvg_id}".lower()
    for kw in BEIN_KEYWORDS:
        if kw in text:
            return True
    name_lower = name.lower().strip()
    for ch_name in BEIN_CHANNEL_NAMES:
        if ch_name.lower() in name_lower or name_lower in ch_name.lower():
            return True
    return False


async def _scrape_direct_m3u(source: dict) -> list:
    content = await fetch_text(source["url"])
    if not content:
        logger.warning(f"Failed to fetch direct source {source['name']}")
        return []

    channels = []
    lines = content.strip().split("\n")
    for i, line in enumerate(lines):
        line = line.strip()
        if line.startswith("#EXTINF:"):
            if i + 1 < len(lines):
                stream_url = lines[i + 1].strip()
                if stream_url and not stream_url.startswith("#"):
                    meta = _parse_direct_extinf(line)
                    meta["url"] = stream_url
                    channels.append(meta)
    return channels


def _parse_direct_extinf(line: str) -> dict:
    parts = line.split(",")
    display_name = parts[-1].strip() if len(parts) > 1 else "Unknown"
    meta = {"name": display_name, "tvg_id": "", "tvg_name": display_name, "logo": "", "category": "Sports"}
    for segment in line.split('"'):
        for prefix, key in [("tvg-id=", "tvg_id"), ("tvg-name=", "tvg_name"), ("tvg-logo=", "tvg_logo"), ("group-title=", "category")]:
            if segment.startswith(prefix):
                meta[key] = segment.replace(prefix, "").strip('" ')
    return meta


class BeinScraper:
    async def get_all_channels(self) -> list:
        all_channels = []
        for source in BEIN_M3U_SOURCES:
            try:
                if source["type"] == "m3u":
                    result = await parse_m3u(source["url"], source_name=source["name"], filter_func=_is_be_in)
                elif source["type"] == "direct":
                    result = await _scrape_direct_m3u(source)
                else:
                    result = []
                all_channels.extend(result)
                logger.info(f"Found {len(result)} beIN channels from {source['name']}")
            except Exception as e:
                logger.error(f"Failed to process source {source['name']}: {e}")

        result = deduplicate_by_url(all_channels)
        logger.info(f"beIN scrape complete: {len(result)} unique channels")
        return result

    async def scrape_all_sources(self) -> list:
        return await self.get_all_channels()

    async def close(self):
        await close_session()

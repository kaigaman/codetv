from typing import Optional, Callable
from loguru import logger

from m3u_parser import parse_m3u, deduplicate_by_url
from http_client import close_session


INTERNATIONAL_SOURCES = [
    {"name": "iptv-org-news", "url": "https://iptv-org.github.io/iptv/categories/news.m3u", "type": "m3u"},
    {"name": "iptv-org-general", "url": "https://iptv-org.github.io/iptv/categories/general.m3u", "type": "m3u"},
    {"name": "iptv-org-entertainment", "url": "https://iptv-org.github.io/iptv/categories/entertainment.m3u", "type": "m3u"},
    {"name": "Free-TV Global", "url": "https://raw.githubusercontent.com/Free-TV/IPTV/master/playlist.m3u8", "type": "m3u-filtered"},
]

MAJOR_NETWORK_KEYWORDS = [
    "bbc", "cnn", "al jazeera", "france 24", "dw", "rt ",
    "euronews", "sky news", "fox news", "msnbc", "abc news",
    "cbs news", "nbc news", "nhk", "cctv", "cgtn",
    "trt world", "presstv", "i24news", "africanews",
    "bloomberg", "cnbc", "reuters", "franceinfo",
]

NEWS_KEYWORDS = [
    "news", "24", "world", "international", "global",
    "bbc", "cnn", "aljazeera", "france24", "dw",
    "euronews", "skynews", "foxnews", "bloomberg",
    "cnbc", "reuters", "africanews",
]


def _is_news(name: str, group: str = "", tvg_id: str = "", country: str = "") -> bool:
    text = f"{name} {group} {tvg_id}".lower()
    for kw in NEWS_KEYWORDS:
        if kw in text:
            return True
    return False


def _is_major_network(name: str, group: str = "", tvg_id: str = "", country: str = "") -> bool:
    text = f"{name} {group} {tvg_id}".lower()
    for kw in MAJOR_NETWORK_KEYWORDS:
        if kw in text:
            return True
    return False


class InternationalScraper:
    async def get_news_channels(self) -> list:
        channels = []
        for source in INTERNATIONAL_SOURCES:
            try:
                if source["type"] == "m3u":
                    result = await parse_m3u(source["url"], source_name=source["name"])
                elif source["type"] == "m3u-filtered":
                    result = await parse_m3u(source["url"], source_name=source["name"], filter_func=_is_news)
                else:
                    result = []
                channels.extend(result)
                logger.info(f"Found {len(result)} channels from {source['name']}")
            except Exception as e:
                logger.error(f"Failed to process source {source['name']}: {e}")

        result = deduplicate_by_url(channels)
        logger.info(f"International news scrape complete: {len(result)} unique channels")
        return result

    async def get_major_networks(self) -> list:
        channels = []
        for source in INTERNATIONAL_SOURCES:
            try:
                filter_fn = _is_major_network
                result = await parse_m3u(source["url"], source_name=source["name"], filter_func=filter_fn)
                channels.extend(result)
            except Exception:
                continue

        result = deduplicate_by_url(channels)
        logger.info(f"Major network scrape complete: {len(result)} unique channels")
        return result

    async def scrape_all_sources(self) -> list:
        return await self.get_news_channels()

    async def close(self):
        await close_session()

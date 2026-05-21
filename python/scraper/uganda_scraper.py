import aiohttp
from bs4 import BeautifulSoup
from typing import Optional, Callable
from loguru import logger

from m3u_parser import parse_m3u, deduplicate_by_url
from http_client import fetch_text, close_session


UGANDA_SOURCES = [
    {"name": "iptv-org", "url": "https://iptv-org.github.io/iptv/countries/ug.m3u", "type": "m3u", "priority": 1},
    {"name": "iptv-org-api", "url": "https://iptv-org.github.io/api/channels.json", "type": "iptv-org-api", "priority": 2},
    {"name": "Free-TV", "url": "https://raw.githubusercontent.com/Free-TV/IPTV/master/playlist.m3u8", "type": "m3u-filtered", "priority": 3},
    {"name": "UBC TV", "url": "https://ubc.go.ug/tv/ubc-tv/", "type": "website", "priority": 5},
    {"name": "NBS TV", "url": "https://nextmedia.co.ug/", "type": "website", "priority": 6},
    {"name": "Bukedde TV", "url": "https://www.bukedde.co.ug/", "type": "website", "priority": 7},
    {"name": "NTV Uganda", "url": "https://www.ntv.co.ug/", "type": "website", "priority": 8},
    {"name": "Salt TV", "url": "https://salttelevision.com/", "type": "website", "priority": 9},
    {"name": "Spark TV", "url": "https://sparktv.co.ug/", "type": "website", "priority": 10},
    {"name": "Urban TV", "url": "https://urbantv.co.ug/", "type": "website", "priority": 11},
    {"name": "Galaxy TV", "url": "https://galaxyfm.co.ug/", "type": "website", "priority": 12},
]

KNOWN_UGANDA_CHANNELS = [
    "3abn tv uganda", "acw ug tv", "akaboozi", "alpha digital",
    "ark tv", "baba tv", "bbs tv", "be tv", "bethany tv", "bm tv africa",
    "btm tv", "btv", "bukalango tv", "bukedde tv", "bunyoro tv",
    "ccco aspire tv", "chamuka tv", "channel 44 uganda", "channel u",
    "church of uganda family television", "delta tv tukole",
    "doxa tv", "dream tv", "eternal life tv", "excel tv", "face tv",
    "faraja television", "focus of god tv", "fort tv",
    "freedom experience tv", "freedom love zone tv", "freedom movie sphere",
    "fresh tv", "fufa tv", "galaxy tv", "gbn tv", "glorious times tv",
    "gmtv", "gntv", "ground tv", "gtv", "gugudde tv", "hgtv uganda",
    "hope channel uganda", "host tv", "janan schools tv", "kbs tv",
    "king tv", "kitara tv", "krc tv", "kstv uganda", "ktv",
    "lighthouse television", "lit tv", "magic1 tv", "makula kika",
    "makula tv", "mama tv", "manifest television", "moon tv",
    "nbs plus", "nbs sport", "nbs star", "nbs tv",
    "ndejje university tv", "nrg radio visual",
    "ntv uganda", "nyce tv",
    "pearl magic", "pearl magic prime",
    "praise jesus tower tv", "rest tv", "revival tv", "rite tv",
    "rwenzori tv", "salam tv", "salt tv", "sanyuka prime", "sanyuka tv",
    "see tv", "sky television", "smart24 tv", "spark tv",
    "spirit of glory tv", "spirit tv", "star tv", "stv", "tagy tv",
    "tayari west tv", "tbs tv", "top tv", "trumpet of faith tv",
    "trust tv", "turn tv", "tv east", "tv one uganda", "tv west",
    "u24 television", "ubc tv", "uctv", "urban tv", "wan luo tv",
    "wbs tv", "westnile tv", "worship tv",
]


def _is_uganda(name: str, group: str = "", tvg_id: str = "", country: str = "") -> bool:
    if country.upper() == "UG":
        return True
    name_lower = name.lower().strip()
    ug_keywords = ["uganda", "kampala", "bukedde", "nbs", "ntv", "ubc",
                   "sanyuka", "urban tv", "spark tv", "salt tv", "galaxy tv",
                   "gugudde", "wan luo", "baba tv", "bbs tv", "bunyoro",
                   "krc tv", "kbs tv", "ktv", "westnile", "tv west", "tv east"]
    for kw in ug_keywords:
        if kw in name_lower:
            return True
    if name_lower in KNOWN_UGANDA_CHANNELS:
        return True
    return False


class UgandaScraper:
    def __init__(self):
        self.known_channels = []

    async def _scrape_website(self, station_name: str, url: str) -> list:
        html = await fetch_text(url)
        if not html:
            return []

        channels = []
        soup = BeautifulSoup(html, "lxml")

        for video in soup.find_all("video"):
            src = video.get("src")
            if src:
                channels.append({
                    "name": f"{station_name} - Video Stream", "url": src,
                    "source": station_name.lower().replace(" ", "_"), "country": "UG",
                })

        for iframe in soup.find_all("iframe"):
            src = iframe.get("src")
            if src and src.startswith("http"):
                channels.append({
                    "name": f"{station_name} - Embedded", "url": src,
                    "source": station_name.lower().replace(" ", "_"), "country": "UG",
                })

        for a in soup.find_all("a"):
            href = a.get("href", "")
            if ".m3u8" in href:
                if href.startswith("//"):
                    href = "https:" + href
                elif href.startswith("/"):
                    import urllib.parse
                    parsed = urllib.parse.urlparse(url)
                    href = f"{parsed.scheme}://{parsed.netloc}{href}"
                channels.append({
                    "name": f"{station_name} - Stream", "url": href,
                    "source": station_name.lower().replace(" ", "_"), "country": "UG",
                })

        return channels

    async def _parse_iptv_org_api(self, url: str) -> list:
        import aiohttp
        session = await self._get_session()
        try:
            async with session.get(url) as resp:
                all_channels = await resp.json()
        except Exception as e:
            logger.error(f"Failed to fetch iptv-org API {url}: {e}")
            return []

        channels = []
        for ch in all_channels:
            country_code = (ch.get("country", {}) or {}).get("code", "")
            if country_code != "UG":
                continue
            categories = ch.get("categories", [])
            channels.append({
                "name": ch.get("name", "Unknown"),
                "url": "",
                "tvg_id": ch.get("id", ""),
                "logo": ch.get("logo", ""),
                "category": categories[0] if categories else "General",
                "country": "UG",
                "website": ch.get("website", ""),
                "source": "iptv-org-api",
            })
        return channels

    async def _get_session(self):
        if not hasattr(self, "_session") or self._session is None:
            self._session = aiohttp.ClientSession()
        return self._session

    async def get_all_channels(self) -> list:
        channels = []
        for source in UGANDA_SOURCES:
            try:
                if source["type"] == "m3u":
                    result = await parse_m3u(source["url"], source_name=source["name"])
                elif source["type"] == "m3u-filtered":
                    result = await parse_m3u(source["url"], source_name=source["name"], filter_func=_is_uganda)
                elif source["type"] == "iptv-org-api":
                    result = await self._parse_iptv_org_api(source["url"])
                elif source["type"] == "website":
                    result = await self._scrape_website(source["name"], source["url"])
                else:
                    result = []
                channels.extend(result)
                logger.info(f"Found {len(result)} channels from {source['name']}")
            except Exception as e:
                logger.error(f"Failed to process source {source['name']}: {e}")

        self.known_channels = channels
        return channels

    async def scrape_all_sources(self) -> list:
        logger.info("Starting full Uganda channel scrape")
        channels = await self.get_all_channels()

        deduped = {}
        for ch in channels:
            url = ch.get("url", "")
            name = ch.get("name", "")
            if not name:
                continue
            key = f"{name}|{url}" if url else name
            if key not in deduped:
                deduped[key] = ch
            elif url and not deduped[key].get("url"):
                deduped[key] = ch

        result = list(deduped.values())
        logger.info(f"Uganda scrape complete: {len(result)} unique channels")
        return result

    async def close(self):
        if hasattr(self, "_session") and self._session:
            await self._session.close()
        await close_session()

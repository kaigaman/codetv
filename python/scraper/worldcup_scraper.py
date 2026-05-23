import asyncio
import re
from datetime import datetime, timedelta
from typing import Optional

from loguru import logger

from http_client import fetch_text, close_session

WORLDCUP_SOURCES = [
    {"name": "808ball2", "url": "https://808ball2.com/football.html", "type": "playwright"},
    {"name": "score808", "url": "https://www.score808.tv/football.html", "type": "playwright"},
    {"name": "score808-wc", "url": "https://www.score808.tv/2026worldcup.html", "type": "playwright"},
    {"name": "livesports088", "url": "https://www.livesports088.com/", "type": "playwright"},
]

BROADCASTER_STREAMS = [
    {"name": "BBC iPlayer", "country": "GB", "type": "free", "url": "https://www.bbc.co.uk/iplayer"},
    {"name": "ITVX", "country": "GB", "type": "free", "url": "https://www.itv.com/watch"},
    {"name": "FOX Sports", "country": "US", "type": "free-to-air", "url": "https://www.foxsports.com"},
    {"name": "Telemundo", "country": "US", "type": "free-to-air", "url": "https://www.telemundo.com"},
    {"name": "SBS On Demand", "country": "AU", "type": "free", "url": "https://www.sbs.com.au/ondemand"},
    {"name": "CTV", "country": "CA", "type": "free", "url": "https://www.ctv.ca"},
    {"name": "M6", "country": "FR", "type": "free-to-air", "url": "https://www.6play.fr"},
    {"name": "ARD Mediathek", "country": "DE", "type": "free", "url": "https://www.ardmediathek.de"},
    {"name": "ZDF Mediathek", "country": "DE", "type": "free", "url": "https://www.zdf.de"},
    {"name": "NOS", "country": "NL", "type": "free", "url": "https://nos.nl"},
    {"name": "RAI Play", "country": "IT", "type": "free", "url": "https://www.raiplay.it"},
    {"name": "RTVE", "country": "ES", "type": "free", "url": "https://www.rtve.es"},
    {"name": "TVP", "country": "PL", "type": "free", "url": "https://tvp.pl"},
    {"name": "CazéTV", "country": "BR", "type": "free", "url": "https://www.youtube.com/@CazeTV"},
]

WC_KEYWORDS = [
    "world cup", "fifa", "wc 2026", "worldcup",
    "2026 world cup", "fifa world cup",
]

TEAMS_2026 = [
    "usa", "canada", "mexico", "argentina", "brazil", "uruguay",
    "england", "france", "germany", "spain", "italy", "portugal",
    "netherlands", "belgium", "croatia", "denmark", "switzerland",
    "japan", "south korea", "australia", "iran", "saudi arabia",
    "senegal", "morocco", "nigeria", "ghana", "cameroon", "tunisia",
    "algeria", "egypt", "ivory coast", "mali", "burkina faso",
]


def _is_world_cup(name: str, group: str = "", tvg_id: str = "", country: str = "") -> bool:
    text = f"{name} {group} {tvg_id}".lower()
    for kw in WC_KEYWORDS:
        if kw in text:
            return True
    for team in TEAMS_2026:
        if team in text:
            for kw in [" vs ", " v ", " match", " game", " live", " stream"]:
                if kw in text:
                    return True
    return False


async def _scrape_worldcup_match_page(url: str) -> list:
    html = await fetch_text(url)
    if not html:
        return []

    matches = []
    m3u8_urls = re.findall(r'(https?://[^\s"\'<>]+\.m3u8[^\s"\'<>]*)', html)
    iframe_urls = re.findall(r'<iframe[^>]*src="([^"]+)"', html)

    match_blocks = re.findall(
        r'<div[^>]*class="[^"]*match[^"]*"[^>]*>(.*?)</div>',
        html, re.IGNORECASE | re.DOTALL
    ) or re.findall(
        r'<tr[^>]*class="[^"]*match[^"]*"[^>]*>(.*?)</tr>',
        html, re.IGNORECASE | re.DOTALL
    )

    for block in match_blocks:
        name_match = re.search(r'<[^>]+>([^<]+(?:vs?\.?\s*|v\s*|VS?\s*)[^<]+)<', block, re.IGNORECASE)
        stream_match = re.search(r'(https?://[^\s"\'<>]+\.m3u8[^\s"\'<>]*)', block)
        iframe_match = re.search(r'<iframe[^>]*src="([^"]+)"', block)

        name = name_match.group(1).strip() if name_match else "World Cup Match"
        stream_url = stream_match.group(1).rstrip(".,;\"')") if stream_match else ""
        iframe_src = iframe_match.group(1) if iframe_match else ""

        if _is_world_cup(name):
            matches.append({
                "name": name,
                "stream_url": stream_url,
                "iframe_url": iframe_src if iframe_src.startswith("http") else "",
                "source": url,
            })

    if not matches:
        for stream_url in m3u8_urls:
            matches.append({
                "name": "World Cup Stream",
                "stream_url": stream_url.rstrip(".,;\"')"),
                "iframe_url": "",
                "source": url,
            })

    for iframe_src in iframe_urls:
        if iframe_src.startswith("//"):
            iframe_src = "https:" + iframe_src
        all_streams = [m["stream_url"] for m in matches if m.get("stream_url")]
        if iframe_src.startswith("http") and iframe_src not in all_streams:
            matches.append({
                "name": "World Cup Stream",
                "stream_url": "",
                "iframe_url": iframe_src,
                "source": url,
            })

    return matches


def _get_country_for_broadcaster(name: str) -> str:
    country_map = {
        "bbc": "GB", "itv": "GB",
        "fox": "US", "telemundo": "US",
        "sbs": "AU", "ctv": "CA", "tsn": "CA",
        "m6": "FR", "ard": "DE", "zdf": "DE",
        "nos": "NL", "rai": "IT", "rtve": "ES",
        "tvp": "PL", "caze": "BR",
    }
    for key, code in country_map.items():
        if key in name.lower():
            return code
    return ""


class WorldCupScraper:
    async def get_matches_from_sources(self) -> list:
        all_matches = []
        for source in WORLDCUP_SOURCES:
            try:
                if source["type"] == "playwright":
                    from scraper.eight08_scraper import Eight08Scraper
                    scraper = Eight08Scraper()
                    items = await scraper.get_live_matches()
                    for item in items:
                        for stream_url in item.get("streams", []):
                            if _is_world_cup(item.get("name", "")):
                                all_matches.append({
                                    "name": item["name"],
                                    "stream_url": stream_url,
                                    "source": source["name"],
                                    "country": "",
                                })
                    await scraper.close()
                else:
                    matches = await _scrape_worldcup_match_page(source["url"])
                    all_matches.extend(matches)
                logger.info(f"Found {len(all_matches)} WC matches from {source['name']}")
            except Exception as e:
                logger.error(f"Failed WC scrape from {source['name']}: {e}")

        return all_matches

    async def get_broadcaster_guide(self) -> list:
        return BROADCASTER_STREAMS

    async def get_all_matches(self) -> dict:
        matches = await self.get_matches_from_sources()
        broadcasters = await self.get_broadcaster_guide()

        deduped = {}
        for m in matches:
            key = m.get("stream_url") or m.get("name", "")
            if key and key not in deduped:
                country = _get_country_for_broadcaster(m.get("source", ""))
                m["country"] = country
                deduped[key] = m

        result = list(deduped.values())
        logger.info(f"World Cup scrape: {len(result)} unique matches, {len(broadcasters)} broadcasters")
        return {
            "matches": result,
            "broadcasters": broadcasters,
            "total_matches": len(result),
            "last_updated": datetime.utcnow().isoformat(),
        }

    async def close(self):
        await close_session()

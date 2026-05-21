import aiohttp
import asyncio
import xmltodict
import gzip
import json
from io import BytesIO
from typing import Optional
from loguru import logger


EPG_SOURCES = {
    "ug": None,
    "us": "http://epg.streamstv.me/epg/guide-usa.xml.gz",
    "uk": "http://epg.streamstv.me/epg/guide-uk.xml.gz",
    "ca": "http://epg.streamstv.me/epg/guide-canada.xml.gz",
    "de": "http://epg.streamstv.me/epg/guide-germany.xml.gz",
    "in": "http://epg.streamstv.me/epg/guide-india.xml.gz",
    "it": "http://epg.streamstv.me/epg/guide-italy.xml.gz",
    "br": "http://epg.streamstv.me/epg/guide-brazil.xml.gz",
    "fr": "http://epg.streamstv.me/epg/guide-france.xml.gz",
    "es": "http://epg.streamstv.me/epg/guide-spain.xml.gz",
    "ng": "http://epg.streamstv.me/epg/guide-nigeria.xml.gz",
    "ke": "http://epg.streamstv.me/epg/guide-kenya.xml.gz",
}


class EPGFetcher:
    def __init__(self):
        self.session: Optional[aiohttp.ClientSession] = None

    async def _get_session(self) -> aiohttp.ClientSession:
        if self.session is None:
            self.session = aiohttp.ClientSession()
        return self.session

    async def fetch_xmltv(self, url: str) -> Optional[dict]:
        session = await self._get_session()
        try:
            async with session.get(url, timeout=30) as resp:
                if resp.status != 200:
                    logger.warning(f"EPG fetch failed: {url} -> {resp.status}")
                    return None

                content = await resp.read()

                if url.endswith(".gz"):
                    try:
                        with gzip.GzipFile(fileobj=BytesIO(content)) as f:
                            xml_data = f.read()
                    except Exception:
                        xml_data = content
                else:
                    xml_data = content

                try:
                    parsed = xmltodict.parse(xml_data)
                    return parsed
                except Exception as e:
                    logger.error(f"Failed to parse XMLTV: {e}")
                    return None

        except asyncio.TimeoutError:
            logger.warning(f"EPG fetch timeout: {url}")
            return None
        except Exception as e:
            logger.error(f"EPG fetch error: {url} -> {e}")
            return None

    async def fetch_for_country(self, country: str) -> bool:
        url = EPG_SOURCES.get(country)
        if not url:
            logger.warning(f"No EPG source for country: {country}")
            return False

        logger.info(f"Fetching EPG for {country} from {url}")
        data = await self.fetch_xmltv(url)

        if not data or "tv" not in data:
            logger.warning(f"No valid EPG data for {country}")
            return False

        programmes = data["tv"].get("programme", [])
        if isinstance(programmes, dict):
            programmes = [programmes]

        channels = data["tv"].get("channel", [])
        if isinstance(channels, dict):
            channels = [channels]

        logger.info(f"EPG for {country}: {len(channels)} channels, {len(programmes)} programmes")
        return True

    async def fetch_all(self) -> dict:
        results = {}
        for country in EPG_SOURCES:
            result = await self.fetch_for_country(country)
            results[country] = result
        return results

    async def close(self):
        if self.session:
            await self.session.close()

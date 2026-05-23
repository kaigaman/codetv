import asyncio
import re
from typing import Optional

from loguru import logger

from http_client import fetch_text, close_session
from scraper.eight08_sources import EIGHT08_DOMAINS, SPORT_PAGES, STREAM_SELECTORS, MATCH_CONTAINER_SELECTORS

try:
    from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeout
    HAS_PLAYWRIGHT = True
except ImportError:
    HAS_PLAYWRIGHT = False
    logger.warning("Playwright not available for 808 scraping")


class Eight08Scraper:
    def __init__(self):
        self.browser = None
        self.context = None

    async def _ensure_browser(self):
        if self.browser is None and HAS_PLAYWRIGHT:
            p = await async_playwright().start()
            self.browser = await p.chromium.launch(headless=True)
            self.context = await self.browser.new_context(
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
                viewport={"width": 1280, "height": 720},
            )

    async def _extract_streams_from_page(self, page, url: str) -> list:
        streams = []
        for selector in STREAM_SELECTORS:
            try:
                elements = await page.query_selector_all(selector)
                for el in elements:
                    tag = await el.get_attribute("href") or await el.get_attribute("src") or ""
                    if tag and (".m3u8" in tag or tag.startswith("http")):
                        if tag.startswith("//"):
                            tag = "https:" + tag
                        streams.append(tag)
            except Exception:
                continue

        content = await page.content()
        m3u8_urls = re.findall(r'(https?://[^\s"\'<>]+\.m3u8[^\s"\'<>]*)', content)
        for u in m3u8_urls:
            u = u.rstrip(".,;\"')")
            if u not in streams:
                streams.append(u)

        iframe_urls = re.findall(r'<iframe[^>]*src="([^"]+)"', content)
        for u in iframe_urls:
            if u.startswith("//"):
                u = "https:" + u
            if u.startswith("http") and u not in streams:
                streams.append(u)

        return streams

    async def _scrape_domain_playwright(self, domain: dict) -> list:
        if not HAS_PLAYWRIGHT:
            return []
        await self._ensure_browser()
        if not self.context:
            return []

        all_matches = []
        for sport_page in SPORT_PAGES:
            url = domain["url"] + sport_page
            page = None
            try:
                page = await self.context.new_page()
                await page.goto(url, timeout=30000, wait_until="domcontentloaded")
                await page.wait_for_timeout(5000)

                match_elements = []
                for sel in MATCH_CONTAINER_SELECTORS:
                    try:
                        els = await page.query_selector_all(sel)
                        if els:
                            match_elements = els
                            break
                    except Exception:
                        continue

                if not match_elements:
                    streams = await self._extract_streams_from_page(page, url)
                    if streams:
                        all_matches.append({
                            "name": f"Live Stream - {domain['name']}",
                            "url": url,
                            "streams": streams,
                            "source": domain["name"],
                            "sport": sport_page.split(".")[0].strip("/") or "general",
                        })
                    continue

                for el in match_elements:
                    try:
                        text = await el.inner_text()
                        name = text.strip()[:100] if text else "Live Match"
                        streams = await self._extract_streams_from_page(page, url)
                        all_matches.append({
                            "name": name,
                            "url": url,
                            "streams": streams,
                            "source": domain["name"],
                            "sport": sport_page.split(".")[0].strip("/") or "general",
                        })
                    except Exception:
                        continue

                logger.info(f"Playwright scraped {len(all_matches)} items from {url}")
            except PlaywrightTimeout:
                logger.warning(f"Timeout loading {url}")
            except Exception as e:
                logger.error(f"Failed to scrape {url}: {e}")
            finally:
                if page:
                    await page.close()

        return all_matches

    async def get_all_channels(self) -> list:
        all_channels = []
        for domain in EIGHT08_DOMAINS:
            try:
                results = await self._scrape_domain_playwright(domain)
                all_channels.extend(results)
            except Exception as e:
                logger.error(f"Failed to process domain {domain['name']}: {e}")

        logger.info(f"808 ecosystem scrape complete: {len(all_channels)} total items")
        return all_channels

    async def get_live_matches(self) -> list:
        all_items = await self.get_all_channels()
        live = [m for m in all_items if m.get("streams")]
        logger.info(f"Found {len(live)} live matches with streams")
        return live

    async def close(self):
        if self.browser:
            await self.browser.close()
            self.browser = None
            self.context = None
        await close_session()

import asyncio
import re
from typing import Optional
from loguru import logger

try:
    from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeout
    HAS_PLAYWRIGHT = True
except ImportError:
    HAS_PLAYWRIGHT = False
    logger.warning("Playwright not installed. JS-heavy site scraping disabled.")


UGANDA_JS_SITES = [
    {
        "name": "UBC TV",
        "url": "https://ubc.go.ug/tv/ubc-tv/",
        "selectors": ["video source[src]", "video[src]", "iframe[src*='m3u8']", "iframe[src*='stream']"],
    },
    {
        "name": "NBS TV",
        "url": "https://nextmedia.co.ug/",
        "selectors": ["video source[src]", "video[src]", "iframe[src*='m3u8']", "iframe[src*='stream']"],
    },
    {
        "name": "Spark TV",
        "url": "https://sparktv.co.ug/",
        "selectors": ["video source[src]", "video[src]", "iframe[src*='m3u8']", "iframe[src*='stream']"],
    },
    {
        "name": "Salt TV",
        "url": "https://salttelevision.com/",
        "selectors": ["video source[src]", "video[src]", "iframe[src*='m3u8']", "iframe[src*='stream']"],
    },
    {
        "name": "NTV Uganda",
        "url": "https://www.ntv.co.ug/",
        "selectors": ["video source[src]", "video[src]", "iframe[src*='m3u8']", "iframe[src*='stream']"],
    },
    {
        "name": "Urban TV",
        "url": "https://urbantv.co.ug/",
        "selectors": ["video source[src]", "video[src]", "iframe[src*='m3u8']", "iframe[src*='stream']"],
    },
    {
        "name": "Galaxy TV",
        "url": "https://galaxyfm.co.ug/",
        "selectors": ["video source[src]", "video[src]", "iframe[src*='m3u8']", "iframe[src*='stream']"],
    },
]


class PlaywrightScraper:
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

    async def scrape_site(self, name: str, url: str, selectors: list) -> list:
        if not HAS_PLAYWRIGHT:
            logger.warning("Playwright not available, cannot scrape JS site: {name}")
            return []

        await self._ensure_browser()
        if not self.context:
            return []

        channels = []
        page = None
        try:
            page = await self.context.new_page()
            await page.goto(url, timeout=30000, wait_until="domcontentloaded")
            await page.wait_for_timeout(3000)

            for selector in selectors:
                try:
                    elements = await page.query_selector_all(selector)
                    for el in elements:
                        src = await el.get_attribute("src")
                        if src and (".m3u8" in src or src.startswith("http")):
                            if src.startswith("//"):
                                src = "https:" + src
                            channels.append({
                                "name": name,
                                "url": src,
                                "source": name.lower().replace(" ", "_"),
                                "country": "UG",
                                "method": "playwright",
                            })
                except Exception:
                    continue

            page_content = await page.content()
            m3u8_urls = re.findall(r'(https?://[^\s"\']+\.m3u8[^\s"\']*)', page_content)
            for m3u8_url in m3u8_urls:
                m3u8_url = m3u8_url.rstrip(".,;\"')")
                if not any(c["url"] == m3u8_url for c in channels):
                    channels.append({
                        "name": name,
                        "url": m3u8_url,
                        "source": name.lower().replace(" ", "_"),
                        "country": "UG",
                        "method": "playwright-regex",
                    })

            logger.info(f"Playwright scraped {len(channels)} streams from {name}")

        except PlaywrightTimeout:
            logger.warning(f"Timeout loading {name} at {url}")
        except Exception as e:
            logger.error(f"Playwright scrape failed for {name}: {e}")
        finally:
            if page:
                await page.close()

        return channels

    async def scrape_all(self) -> list:
        if not HAS_PLAYWRIGHT:
            logger.warning("Playwright not available")
            return []

        await self._ensure_browser()
        all_channels = []

        for site in UGANDA_JS_SITES:
            channels = await self.scrape_site(site["name"], site["url"], site["selectors"])
            all_channels.extend(channels)
            await asyncio.sleep(1)

        return all_channels

    async def close(self):
        if self.browser:
            await self.browser.close()
            self.browser = None
            self.context = None

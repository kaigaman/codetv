import asyncio
import random
import time
from typing import Optional

import aiohttp
from loguru import logger

FREE_PROXY_SOURCES = [
    "https://raw.githubusercontent.com/proxifly/free-proxy-list/main/proxies/all/data.txt",
    "https://raw.githubusercontent.com/TheSpeedX/SOCKS-Proxy-List/master/socks5.txt",
    "https://raw.githubusercontent.com/ShiftyTR/Proxy-List/master/socks5.txt",
    "https://raw.githubusercontent.com/hookzof/socks5_list/master/proxy.txt",
    "https://raw.githubusercontent.com/jetkai/proxy-list/main/online-proxies/txt/proxies-socks5.txt",
    "https://www.proxy-list.download/api/v1/get?type=socks5",
]

PROXY_POOL = []
_last_fetch = 0


def _country_for_channel(channel_name: str, country_code: str = "") -> str:
    code_map = {
        "tr": "TR", "qa": "QA", "sa": "SA", "ae": "AE",
        "gb": "GB", "us": "US", "fr": "FR", "de": "DE",
        "es": "ES", "it": "IT", "au": "AU", "ca": "CA",
        "ma": "MA", "dz": "DZ", "tn": "TN", "eg": "EG",
        "id": "ID", "ph": "PH", "in": "IN",
    }
    if country_code and country_code.upper() in code_map.values():
        return country_code.upper()
    name_lower = channel_name.lower()
    if "tr:" in name_lower or "turkish" in name_lower:
        return "TR"
    if "fr:" in name_lower or "french" in name_lower:
        return "FR"
    if "us:" in name_lower or "usa" in name_lower:
        return "US"
    if "gb:" in name_lower or "uk " in name_lower or name_lower.startswith("bbc"):
        return "GB"
    if "au:" in name_lower or "australia" in name_lower:
        return "AU"
    return ""


async def _fetch_proxy_list(url: str, session: aiohttp.ClientSession) -> list:
    try:
        async with session.get(url, timeout=aiohttp.ClientTimeout(total=15)) as resp:
            if resp.status == 200:
                text = await resp.text()
                proxies = []
                for line in text.strip().split("\n"):
                    line = line.strip()
                    if ":" in line and not line.startswith("#"):
                        parts = line.split(":")
                        if len(parts) == 2:
                            host, port = parts[0], parts[1]
                            port = port.split("@")[0].strip()
                            try:
                                int(port)
                                proxies.append(f"socks5://{host}:{port}")
                            except ValueError:
                                pass
                return proxies
    except Exception as e:
        logger.debug(f"Failed to fetch proxy list {url}: {e}")
    return []


async def refresh_proxy_pool():
    global PROXY_POOL, _last_fetch
    if time.time() - _last_fetch < 120:
        return
    _last_fetch = time.time()

    all_proxies = []
    async with aiohttp.ClientSession() as session:
        tasks = [_fetch_proxy_list(url, session) for url in FREE_PROXY_SOURCES]
        results = await asyncio.gather(*tasks, return_exceptions=True)
        for r in results:
            if isinstance(r, list):
                all_proxies.extend(r)

    PROXY_POOL = list(set(all_proxies))
    logger.info(f"Refreshed proxy pool: {len(PROXY_POOL)} proxies available")


async def get_proxy(country: str = "") -> Optional[str]:
    await refresh_proxy_pool()
    if not PROXY_POOL:
        return None
    return random.choice(PROXY_POOL)


async def test_proxy(proxy: str, test_url: str = "http://httpbin.org/ip", timeout: int = 10) -> bool:
    try:
        connector = aiohttp.TCPConnector()
        async with aiohttp.ClientSession(connector=connector) as session:
            async with session.get(
                test_url,
                proxy=proxy,
                timeout=aiohttp.ClientTimeout(total=timeout),
            ) as resp:
                return resp.status == 200
    except Exception:
        return False


async def get_working_proxy(country: str = "", retries: int = 5) -> Optional[str]:
    await refresh_proxy_pool()
    candidates = PROXY_POOL.copy()
    random.shuffle(candidates)
    for proxy in candidates[:retries * 3]:
        valid = await test_proxy(proxy)
        if valid:
            logger.debug(f"Working proxy found: {proxy}")
            return proxy
    return await get_proxy(country)


def get_country_code_for_channel(channel: dict) -> str:
    code = channel.get("country", "")
    name = channel.get("name", "")
    return _country_for_channel(name, code)

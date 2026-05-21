import asyncio
import aiohttp
from typing import Optional
from loguru import logger


_session: Optional[aiohttp.ClientSession] = None
_config = {
    "timeout_total": 30,
    "timeout_connect": 10,
    "conn_limit": 100,
    "conn_limit_per_host": 10,
    "retries": 3,
    "retry_delay": 1.0,
}


def configure(**kwargs):
    _config.update(kwargs)


async def get_session() -> aiohttp.ClientSession:
    global _session
    if _session is None or _session.closed:
        timeout = aiohttp.ClientTimeout(
            total=_config["timeout_total"],
            connect=_config["timeout_connect"],
        )
        connector = aiohttp.TCPConnector(
            limit=_config["conn_limit"],
            limit_per_host=_config["conn_limit_per_host"],
            force_close=False,
            enable_cleanup_closed=True,
        )
        _session = aiohttp.ClientSession(
            timeout=timeout,
            connector=connector,
            headers={"User-Agent": "CODETV/1.0"},
        )
    return _session


async def fetch(url: str, method: str = "GET", **kwargs) -> Optional[aiohttp.ClientResponse]:
    session = await get_session()
    for attempt in range(_config["retries"]):
        try:
            resp = await session.request(method, url, **kwargs)
            if resp.status < 500 or attempt == _config["retries"] - 1:
                return resp
        except (aiohttp.ClientError, asyncio.TimeoutError) as e:
            if attempt == _config["retries"] - 1:
                raise
            logger.debug(f"Retry {attempt + 1}/{_config['retries']} for {url}: {e}")
        await asyncio.sleep(_config["retry_delay"] * (2 ** attempt))
    return None


async def fetch_text(url: str, **kwargs) -> Optional[str]:
    resp = await fetch(url, **kwargs)
    if resp is None:
        return None
    try:
        return await resp.text()
    except Exception:
        return None


async def fetch_json(url: str, **kwargs) -> Optional[dict]:
    resp = await fetch(url, **kwargs)
    if resp is None:
        return None
    try:
        return await resp.json()
    except Exception:
        return None


async def close_session():
    global _session
    if _session and not _session.closed:
        await _session.close()
    _session = None

import asyncio
import time
import aiohttp
import m3u8
from typing import Optional
from urllib.parse import urlparse

from http_client import fetch, close_session
from loguru import logger


class StreamChecker:
    async def check_single(self, url: str, timeout: int = 15) -> dict:
        parsed = urlparse(url)
        if not parsed.scheme or not parsed.netloc:
            return {"url": url, "is_online": False, "error": "invalid_url"}

        start = time.time()

        try:
            resp = await fetch(url, method="GET")
            if resp is None:
                return {"url": url, "is_online": False, "error": "unreachable"}
        except Exception as e:
            return {"url": url, "is_online": False, "error": str(e)[:100]}

        latency = (time.time() - start) * 1000
        status = resp.status

        if status >= 400:
            return {
                "url": url,
                "is_online": False,
                "latency_ms": round(latency, 2),
                "error": f"http_{status}",
            }

        content_type = resp.headers.get("Content-Type", "")
        result = {
            "url": url,
            "is_online": True,
            "latency_ms": round(latency, 2),
            "status": status,
        }

        if ".m3u8" in url or "m3u" in content_type or "application/vnd.apple" in content_type:
            try:
                body = await resp.text()
                probe = self._probe_hls(body, url)
                if probe:
                    result.update(probe)
            except Exception:
                pass

        return result

    def _probe_hls(self, content: str, url: str) -> Optional[dict]:
        try:
            parsed = m3u8.loads(content)
        except Exception:
            return None

        info = {}

        if parsed.is_variant:
            resolutions = []
            bandwidths = []
            for p in parsed.playlists:
                if p.stream_info:
                    if p.stream_info.resolution:
                        resolutions.append(f"{p.stream_info.resolution[0]}x{p.stream_info.resolution[1]}")
                    if p.stream_info.bandwidth:
                        bandwidths.append(p.stream_info.bandwidth)
            if resolutions:
                info["resolution"] = resolutions[-1]
            if bandwidths:
                info["bitrate"] = max(bandwidths)
        elif parsed.segments:
            info["resolution"] = "streaming"
            if parsed.target_duration:
                info["segment_duration"] = parsed.target_duration
        elif parsed.is_endlist:
            info["resolution"] = "vod"

        return info if info else None

    async def check_batch(self, urls: list, concurrency: int = 20) -> list:
        semaphore = asyncio.Semaphore(concurrency)

        async def check(url: str):
            async with semaphore:
                return await self.check_single(url)

        tasks = [check(url) for url in urls]
        results = await asyncio.gather(*tasks, return_exceptions=True)

        final = []
        for i, r in enumerate(results):
            if isinstance(r, Exception):
                final.append({"url": urls[i], "is_online": False, "error": str(r)[:100]})
            else:
                final.append(r)
        return final

    async def close(self):
        await close_session()

from typing import Callable, Optional
from http_client import fetch_text
from loguru import logger


def parse_extinf(line: str) -> dict:
    parts = line.split(",")
    display_name = parts[-1].strip() if len(parts) > 1 else "Unknown"

    meta = {
        "tvg_id": "",
        "tvg_name": "",
        "tvg_logo": "",
        "group": "",
        "tvg_country": "",
        "radio": False,
    }

    for segment in line.split('"'):
        for prefix, key in [
            ("tvg-id=", "tvg_id"),
            ("tvg-name=", "tvg_name"),
            ("tvg-logo=", "tvg_logo"),
            ("group-title=", "group"),
            ("tvg-country=", "tvg_country"),
        ]:
            if segment.startswith(prefix):
                meta[key] = segment.replace(prefix, "").strip('" ')

    if "radio=" in line:
        meta["radio"] = True

    return {
        "name": display_name,
        "tvg_id": meta["tvg_id"],
        "tvg_name": meta["tvg_name"] or display_name,
        "logo": meta["tvg_logo"],
        "category": meta["group"],
        "country": meta["tvg_country"],
        "is_radio": meta["radio"],
    }


async def parse_m3u(
    url: str,
    source_name: str = "m3u",
    filter_func: Optional[Callable[[str, str, str, str], bool]] = None,
) -> list:
    content = await fetch_text(url)
    if not content:
        logger.error(f"Failed to fetch M3U {url}")
        return []

    channels = []
    lines = content.strip().split("\n")
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        if line.startswith("#EXTINF:"):
            if i + 1 < len(lines):
                stream_url = lines[i + 1].strip()
                if stream_url and not stream_url.startswith("#"):
                    meta = parse_extinf(line)

                    if filter_func and not filter_func(meta["name"], meta["category"], meta["tvg_id"], meta["country"]):
                        i += 2
                        continue

                    channels.append({
                        "name": meta["name"],
                        "url": stream_url,
                        "tvg_id": meta["tvg_id"],
                        "logo": meta["logo"],
                        "category": meta["category"],
                        "country": meta["country"],
                        "source": source_name,
                    })
            i += 2
        else:
            i += 1

    return channels


async def parse_m3u_content(
    content: str,
    source_name: str = "m3u",
    filter_func: Optional[Callable[[str, str, str, str], bool]] = None,
) -> list:
    channels = []
    lines = content.strip().split("\n")
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        if line.startswith("#EXTINF:"):
            if i + 1 < len(lines):
                stream_url = lines[i + 1].strip()
                if stream_url and not stream_url.startswith("#"):
                    meta = parse_extinf(line)

                    if filter_func and not filter_func(meta["name"], meta["category"], meta["tvg_id"], meta["country"]):
                        i += 2
                        continue

                    channels.append({
                        "name": meta["name"],
                        "url": stream_url,
                        "tvg_id": meta["tvg_id"],
                        "logo": meta["logo"],
                        "category": meta["category"],
                        "country": meta["country"],
                        "source": source_name,
                    })
            i += 2
        else:
            i += 1

    return channels


def deduplicate_by_url(channels: list) -> list:
    seen = {}
    for ch in channels:
        url = ch.get("url", "")
        if url and url not in seen:
            seen[url] = ch
    return list(seen.values())

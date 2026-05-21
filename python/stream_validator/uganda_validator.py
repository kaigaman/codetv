import asyncio
import time
from loguru import logger

from stream_validator.checker import StreamChecker
from stream_validator.db import (
    get_channels_with_urls,
    update_channels_batch,
    get_validation_summary,
    reset_all_online_status,
)


async def validate_uganda(concurrency: int = 50) -> dict:
    logger.info("Starting Uganda stream validation")

    channels = get_channels_with_urls(country_code="ug")
    if not channels:
        logger.warning("No Uganda channels with stream URLs found in database")
        return {"checked": 0, "working": 0, "dead": 0, "error": "no_channels"}

    logger.info(f"Found {len(channels)} Uganda channels with stream URLs to check")

    checker = StreamChecker()
    urls = [ch["url"] for ch in channels]

    start = time.time()
    results = await checker.check_batch(urls, concurrency=concurrency)
    elapsed = time.time() - start

    enriched = []
    for ch, result in zip(channels, results):
        result["channel_id"] = ch["id"]
        result["channel_name"] = ch["name"]
        enriched.append(result)

    update_channels_batch(enriched)

    working = sum(1 for r in enriched if r.get("is_online"))
    dead = sum(1 for r in enriched if not r.get("is_online"))

    summary = get_validation_summary(country_code="ug")
    summary.update({
        "checked": len(enriched),
        "working": working,
        "dead": dead,
        "elapsed_seconds": round(elapsed, 2),
        "avg_check_ms": round((elapsed / len(enriched)) * 1000, 2) if enriched else 0,
    })

    logger.info(f"Uganda validation complete: {working}/{len(enriched)} working in {elapsed:.1f}s")
    return summary


async def validate_all(concurrency: int = 50, batch_size: int = 200) -> dict:
    logger.info("Starting global stream validation")

    channels = get_channels_with_urls(country_code=None)
    if not channels:
        return {"checked": 0, "working": 0, "dead": 0, "error": "no_channels"}

    logger.info(f"Found {len(channels)} channels with stream URLs to check globally")

    checker = StreamChecker()
    total_checked = 0
    total_working = 0
    all_results = []
    start = time.time()

    for i in range(0, len(channels), batch_size):
        batch = channels[i : i + batch_size]
        urls = [ch["url"] for ch in batch]

        batch_start = time.time()
        results = await checker.check_batch(urls, concurrency=concurrency)
        batch_elapsed = time.time() - batch_start

        enriched = []
        for ch, result in zip(batch, results):
            result["channel_id"] = ch["id"]
            result["channel_name"] = ch["name"]
            enriched.append(result)

        update_channels_batch(enriched)
        all_results.extend(enriched)

        batch_working = sum(1 for r in enriched if r.get("is_online"))
        total_checked += len(enriched)
        total_working += batch_working

        logger.info(
            f"Batch {i//batch_size + 1}/{(len(channels)-1)//batch_size + 1}: "
            f"{batch_working}/{len(enriched)} working in {batch_elapsed:.1f}s"
        )

    elapsed = time.time() - start
    total_dead = total_checked - total_working

    summary = {
        "checked": total_checked,
        "working": total_working,
        "dead": total_dead,
        "elapsed_seconds": round(elapsed, 2),
        "batches": (len(channels) - 1) // batch_size + 1,
    }

    logger.info(f"Global validation complete: {total_working}/{total_checked} working in {elapsed:.1f}s")
    return summary


async def reset_and_validate_uganda(concurrency: int = 50) -> dict:
    reset = reset_all_online_status()
    logger.info(f"Reset {reset} channels to is_online=false")
    result = await validate_uganda(concurrency=concurrency)
    result["reset_count"] = reset
    return result

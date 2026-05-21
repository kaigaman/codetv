from fastapi import APIRouter, HTTPException, BackgroundTasks, Query
from pydantic import BaseModel
from typing import Optional

from stream_validator.checker import StreamChecker
from stream_validator.uganda_validator import validate_uganda, validate_all, reset_and_validate_uganda
from stream_validator.db import get_validation_summary
from http_client import close_session

router = APIRouter()


class StreamCheckRequest(BaseModel):
    url: str
    timeout: Optional[int] = 10


class StreamCheckResponse(BaseModel):
    url: str
    is_online: bool
    latency_ms: Optional[float] = None
    resolution: Optional[str] = None
    bitrate: Optional[int] = None
    error: Optional[str] = None


@router.post("/stream/check", response_model=StreamCheckResponse)
async def check_stream(req: StreamCheckRequest):
    checker = StreamChecker()
    result = await checker.check_single(req.url, req.timeout)
    return result


@router.get("/uganda/channels")
async def get_ugandan_channels():
    from scraper.uganda_scraper import UgandaScraper
    scraper = UgandaScraper()
    try:
        channels = await scraper.get_all_channels()
        return {"country": "uganda", "channels": channels, "total": len(channels)}
    finally:
        await scraper.close()


@router.post("/uganda/scrape")
async def scrape_uganda(background_tasks: BackgroundTasks):
    from scraper.uganda_scraper import UgandaScraper
    async def run():
        scraper = UgandaScraper()
        try:
            await scraper.scrape_all_sources()
        finally:
            await scraper.close()
    background_tasks.add_task(run)
    return {"status": "started", "message": "Scraping Ugandan channels in background"}


@router.get("/epg/fetch")
async def fetch_epg(country: str = "ug"):
    from epg_processor.fetcher import EPGFetcher
    fetcher = EPGFetcher()
    result = await fetcher.fetch_for_country(country)
    return {"country": country, "status": "completed" if result else "failed"}


@router.get("/m3u/generate/{country}")
async def generate_m3u(country: str, include_offline: bool = False, format: str = "m3u8"):
    from m3u_generator.generator import M3UGenerator
    gen = M3UGenerator()
    content = await gen.generate_country_playlist(country, include_offline)
    if format == "json":
        return {"country": country, "channels": len(content.split("#EXTINF")), "playlist": content}
    from fastapi.responses import PlainTextResponse
    return PlainTextResponse(content, media_type="audio/x-mpegurl")


@router.get("/m3u/uganda")
async def generate_uganda_m3u():
    from m3u_generator.generator import M3UGenerator
    gen = M3UGenerator()
    content = await gen.generate_uganda_playlist()
    from fastapi.responses import PlainTextResponse
    return PlainTextResponse(content, media_type="audio/x-mpegurl")


@router.post("/stream/validate-uganda")
async def api_validate_uganda(
    concurrency: int = Query(50, description="Concurrent checks"),
    reset_first: bool = Query(False, description="Reset all is_online before validating"),
):
    if reset_first:
        result = await reset_and_validate_uganda(concurrency=concurrency)
    else:
        result = await validate_uganda(concurrency=concurrency)
    return result


@router.post("/stream/validate-all")
async def api_validate_all(
    concurrency: int = Query(50, description="Concurrent checks"),
    batch_size: int = Query(200, description="Channels per batch"),
    reset_first: bool = Query(False, description="Reset all is_online before validating"),
):
    if reset_first:
        from stream_validator.db import reset_all_online_status
        reset = reset_all_online_status()
    result = await validate_all(concurrency=concurrency, batch_size=batch_size)
    if reset_first:
        result["reset_count"] = reset
    return result


@router.get("/stream/summary")
async def api_validation_summary(country: Optional[str] = None):
    summary = get_validation_summary(country_code=country)
    return summary


@router.get("/sports/channels")
async def get_sports_channels():
    from scraper.sports_scraper import SportsScraper
    scraper = SportsScraper()
    try:
        channels = await scraper.get_sports_channels()
        return {"category": "sports", "channels": channels, "total": len(channels)}
    finally:
        await scraper.close()


@router.get("/sports/football")
async def get_football_channels():
    from scraper.sports_scraper import SportsScraper
    scraper = SportsScraper()
    try:
        channels = await scraper.get_football_channels()
        return {"category": "football", "channels": channels, "total": len(channels)}
    finally:
        await scraper.close()


@router.post("/sports/scrape")
async def scrape_sports(background_tasks: BackgroundTasks):
    from scraper.sports_scraper import SportsScraper
    async def run():
        scraper = SportsScraper()
        try:
            await scraper.get_sports_channels()
        finally:
            await scraper.close()
    background_tasks.add_task(run)
    return {"status": "started", "message": "Scraping sports channels in background"}


@router.get("/international/channels")
async def get_international_channels():
    from scraper.international_scraper import InternationalScraper
    scraper = InternationalScraper()
    try:
        channels = await scraper.get_news_channels()
        return {"category": "international", "channels": channels, "total": len(channels)}
    finally:
        await scraper.close()


@router.get("/international/major")
async def get_major_networks():
    from scraper.international_scraper import InternationalScraper
    scraper = InternationalScraper()
    try:
        channels = await scraper.get_major_networks()
        return {"category": "major_networks", "channels": channels, "total": len(channels)}
    finally:
        await scraper.close()


@router.post("/international/scrape")
async def scrape_international(background_tasks: BackgroundTasks):
    from scraper.international_scraper import InternationalScraper
    async def run():
        scraper = InternationalScraper()
        try:
            await scraper.get_news_channels()
        finally:
            await scraper.close()
    background_tasks.add_task(run)
    return {"status": "started", "message": "Scraping international channels in background"}

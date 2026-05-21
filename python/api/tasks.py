from celery import Celery
from celery.schedules import crontab

celery_app = Celery(
    "codetv",
    broker="redis://redis:6379/0",
    backend="redis://redis:6379/0",
)

celery_app.conf.update(
    task_serializer="json",
    accept_content=["json"],
    result_serializer="json",
    timezone="Africa/Kampala",
    enable_utc=True,
    beat_schedule={
        "validate-uganda-streams": {
            "task": "api.tasks.validate_uganda_streams",
            "schedule": crontab(minute="0", hour="*/3"),
        },
        "validate-all-streams-db": {
            "task": "api.tasks.validate_all_streams_db",
            "schedule": crontab(minute="0", hour="*/12"),
        },
        "scrape-uganda-channels": {
            "task": "api.tasks.scrape_uganda_channels",
            "schedule": crontab(minute="0", hour="*/12"),
        },
        "fetch-epg-data": {
            "task": "api.tasks.fetch_epg_data",
            "schedule": crontab(minute="30", hour="*/4"),
        },
    },
)


@celery_app.task
def scrape_uganda_channels():
    from scraper.uganda_scraper import UgandaScraper
    import asyncio

    async def run():
        scraper = UgandaScraper()
        try:
            channels = await scraper.scrape_all_sources()
            return {"scraped": len(channels)}
        finally:
            await scraper.close()

    return asyncio.run(run())


@celery_app.task
def fetch_epg_data():
    from epg_processor.fetcher import EPGFetcher
    import asyncio

    async def run():
        fetcher = EPGFetcher()
        result = await fetcher.fetch_for_country("ug")
        return {"success": result}

    return asyncio.run(run())


@celery_app.task(bind=True, max_retries=3, default_retry_delay=60)
def validate_uganda_streams(self, concurrency: int = 50, reset_first: bool = False):
    from stream_validator.uganda_validator import validate_uganda, reset_and_validate_uganda
    import asyncio

    async def run():
        if reset_first:
            return await reset_and_validate_uganda(concurrency=concurrency)
        return await validate_uganda(concurrency=concurrency)

    try:
        return asyncio.run(run())
    except Exception as exc:
        raise self.retry(exc=exc)


@celery_app.task(bind=True, max_retries=2, default_retry_delay=120)
def validate_all_streams_db(self, concurrency: int = 50, batch_size: int = 200):
    from stream_validator.uganda_validator import validate_all
    import asyncio

    async def run():
        return await validate_all(concurrency=concurrency, batch_size=batch_size)

    try:
        return asyncio.run(run())
    except Exception as exc:
        raise self.retry(exc=exc)

import os
import time
from typing import Optional
from urllib.parse import urlparse
from sqlalchemy import create_engine, text, inspect
from sqlalchemy.pool import QueuePool
from loguru import logger


DB_HOST = os.getenv("MYSQL_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("MYSQL_PORT", "3306"))
DB_NAME = os.getenv("MYSQL_DATABASE", "codetv")
DB_USER = os.getenv("MYSQL_USER", "codetv")
DB_PASS = os.getenv("MYSQL_PASSWORD", "codetv_pass")


def _connection_string() -> str:
    return f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME}?charset=utf8mb4"


def _create_engine():
    return create_engine(
        _connection_string(),
        poolclass=QueuePool,
        pool_size=5,
        max_overflow=10,
        pool_pre_ping=True,
        echo=False,
    )


engine = _create_engine()


def wait_for_db(timeout: int = 30):
    deadline = time.time() + timeout
    while time.time() < deadline:
        try:
            with engine.connect() as conn:
                conn.execute(text("SELECT 1"))
                return True
        except Exception:
            time.sleep(1)
    raise RuntimeError(f"Could not connect to MySQL after {timeout}s")


def get_channel_count(country_code: Optional[str] = None) -> int:
    with engine.connect() as conn:
        if country_code:
            sql = text("""
                SELECT COUNT(*) FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code AND c.stream_url IS NOT NULL AND c.stream_url != ''
            """)
            result = conn.execute(sql, {"code": country_code})
        else:
            sql = text("""
                SELECT COUNT(*) FROM channels
                WHERE stream_url IS NOT NULL AND stream_url != ''
            """)
            result = conn.execute(sql)
        return result.scalar() or 0


def get_channels_with_urls(country_code: Optional[str] = None, limit: Optional[int] = None) -> list:
    with engine.connect() as conn:
        if country_code:
            sql = text("""
                SELECT c.id, c.name, c.stream_url, c.stream_type, c.slug,
                       c.is_online, c.latency_ms, c.resolution, c.last_checked_at
                FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code
                  AND c.stream_url IS NOT NULL
                  AND c.stream_url != ''
                  AND c.is_active = true
                ORDER BY c.last_checked_at ASC
            """)
            params = {"code": country_code}
        else:
            sql = text("""
                SELECT c.id, c.name, c.stream_url, c.stream_type, c.slug,
                       c.is_online, c.latency_ms, c.resolution, c.last_checked_at
                FROM channels c
                WHERE c.stream_url IS NOT NULL
                  AND c.stream_url != ''
                  AND c.is_active = true
                ORDER BY c.last_checked_at ASC
            """)
            params = {}

        result = conn.execute(sql, params)
        rows = result.mappings().all()

    channels = []
    for row in rows:
        url = str(row["stream_url"]).strip()
        parsed = urlparse(url)
        if not parsed.scheme or not parsed.netloc:
            continue
        channels.append({
            "id": row["id"],
            "name": row["name"],
            "url": url,
            "stream_type": row["stream_type"],
            "slug": row["slug"],
            "is_online": bool(row["is_online"]),
            "latency_ms": float(row["latency_ms"]) if row["latency_ms"] else None,
            "resolution": row["resolution"],
        })

    if limit and len(channels) > limit:
        channels = channels[:limit]

    return channels


def update_channel_status(
    channel_id: int,
    is_online: bool,
    latency_ms: Optional[float] = None,
    resolution: Optional[str] = None,
    error: Optional[str] = None,
):
    with engine.begin() as conn:
        now = "NOW()"
        if is_online:
            sql = text("""
                UPDATE channels
                SET is_online = TRUE,
                    latency_ms = :latency,
                    resolution = COALESCE(:resolution, resolution),
                    last_checked_at = NOW(),
                    last_online_at = NOW()
                WHERE id = :id
            """)
        else:
            sql = text("""
                UPDATE channels
                SET is_online = FALSE,
                    latency_ms = :latency,
                    last_checked_at = NOW()
                WHERE id = :id
            """)
        conn.execute(sql, {
            "id": channel_id,
            "latency": latency_ms,
            "resolution": resolution,
            "error": error,
        })


def update_channels_batch(results: list):
    with engine.begin() as conn:
        for r in results:
            channel_id = r.get("channel_id")
            if not channel_id:
                continue
            is_online = r.get("is_online", False)
            latency = r.get("latency_ms")
            resolution = r.get("resolution")
            error = r.get("error")

            if is_online:
                sql = text("""
                    UPDATE channels
                    SET is_online = TRUE,
                        latency_ms = :latency,
                        resolution = COALESCE(:resolution, resolution),
                        last_checked_at = NOW(),
                        last_online_at = NOW()
                    WHERE id = :id
                """)
            else:
                sql = text("""
                    UPDATE channels
                    SET is_online = FALSE,
                        latency_ms = :latency,
                        last_checked_at = NOW()
                    WHERE id = :id
                """)
            conn.execute(sql, {
                "id": channel_id,
                "latency": latency,
                "resolution": resolution,
            })


def reset_all_online_status():
    with engine.begin() as conn:
        result = conn.execute(text("UPDATE channels SET is_online = FALSE, last_checked_at = NULL"))
        return result.rowcount


def get_validation_summary(country_code: Optional[str] = None) -> dict:
    with engine.connect() as conn:
        if country_code:
            with_url_sql = text("""
                SELECT COUNT(*) FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code AND c.stream_url IS NOT NULL AND c.stream_url != ''
            """)
            total_sql = text("""
                SELECT COUNT(*) FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code
            """)
            online_sql = text("""
                SELECT COUNT(*) FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code AND c.is_online = TRUE
            """)
            offline_sql = text("""
                SELECT COUNT(*) FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code AND c.is_online = FALSE AND c.stream_url IS NOT NULL AND c.stream_url != ''
            """)
            checked_sql = text("""
                SELECT COUNT(*) FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code AND c.last_checked_at IS NOT NULL
            """)
            avg_latency_sql = text("""
                SELECT AVG(c.latency_ms) FROM channels c
                JOIN countries co ON c.country_id = co.id
                WHERE co.code = :code AND c.latency_ms IS NOT NULL
            """)

            with_url = conn.execute(with_url_sql, {"code": country_code}).scalar() or 0
            total = conn.execute(total_sql, {"code": country_code}).scalar() or 0
            online = conn.execute(online_sql, {"code": country_code}).scalar() or 0
            offline = conn.execute(offline_sql, {"code": country_code}).scalar() or 0
            checked = conn.execute(checked_sql, {"code": country_code}).scalar() or 0
            avg_latency = conn.execute(avg_latency_sql, {"code": country_code}).scalar()
        else:
            with_url_sql = text("SELECT COUNT(*) FROM channels WHERE stream_url IS NOT NULL AND stream_url != ''")
            total_sql = text("SELECT COUNT(*) FROM channels")
            online_sql = text("SELECT COUNT(*) FROM channels WHERE is_online = TRUE")
            offline_sql = text("SELECT COUNT(*) FROM channels WHERE is_online = FALSE AND stream_url IS NOT NULL AND stream_url != ''")
            checked_sql = text("SELECT COUNT(*) FROM channels WHERE last_checked_at IS NOT NULL")
            avg_latency_sql = text("SELECT AVG(latency_ms) FROM channels WHERE latency_ms IS NOT NULL")

            with_url = conn.execute(with_url_sql).scalar() or 0
            total = conn.execute(total_sql).scalar() or 0
            online = conn.execute(online_sql).scalar() or 0
            offline = conn.execute(offline_sql).scalar() or 0
            checked = conn.execute(checked_sql).scalar() or 0
            avg_latency = conn.execute(avg_latency_sql).scalar()

    return {
        "total": total,
        "with_stream_url": with_url,
        "online": online,
        "offline": offline,
        "checked": checked,
        "avg_latency_ms": round(float(avg_latency), 2) if avg_latency else None,
    }

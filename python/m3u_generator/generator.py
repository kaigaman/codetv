from datetime import datetime
from loguru import logger

from http_client import fetch_text, close_session


class M3UGenerator:
    async def generate_country_playlist(self, country: str, include_offline: bool = False) -> str:
        url = f"https://iptv-org.github.io/iptv/countries/{country}.m3u"
        content = await fetch_text(url)
        if not content:
            return self._error_playlist(f"No channels found for country: {country}")

        lines = [
            "#EXTM3U",
            f'#PLAYLIST: CODETV - {country.upper()}',
            f'#DESCRIPTION: Free IPTV channels for {country.upper()}',
            f'#GENERATED: {datetime.utcnow().isoformat()}Z',
            f'#SOURCE: CODETV Platform',
            "",
            content,
        ]
        return "\n".join(lines)

    async def generate_uganda_playlist(self) -> str:
        verified = self._get_verified_uganda_channels()

        lines = [
            "#EXTM3U",
            f'#PLAYLIST: CODETV Uganda ({len(verified)} verified channels)',
            '#DESCRIPTION: Free Ugandan IPTV channels - CODETV',
            f'#GENERATED: {datetime.utcnow().isoformat()}Z',
            '#SOURCE: CODETV Platform | iptv-org',
            '#COUNTRY: Uganda (UG)',
            '#LANGUAGES: English, Luganda, Swahili',
            "",
        ]

        for ch in verified:
            extinf = f'#EXTINF:-1 tvg-id="{ch.get("tvg_id", "")}"'
            if ch.get("logo"):
                extinf += f' tvg-logo="{ch["logo"]}"'
            if ch.get("category"):
                extinf += f' group-title="{ch["category"]}"'
            extinf += ' tvg-language="English"'
            extinf += f',{ch.get("name", "Unknown")}'
            if ch.get("latency_ms"):
                extinf += f' [{round(ch["latency_ms"])}ms]'
            lines.append(extinf)
            lines.append(ch.get("url", ""))
            lines.append("")

        playlist = "\n".join(lines)
        logger.info(f"Generated Uganda playlist with {len(verified)} verified channels")
        return playlist

    def _get_verified_uganda_channels(self) -> list:
        try:
            from stream_validator.db import get_channels_with_urls
            channels = get_channels_with_urls(country_code="ug")
            working = [ch for ch in channels if ch.get("is_online")]
            logger.info(f"Found {len(working)} verified working Uganda channels from DB")
            return working
        except Exception as e:
            logger.error(f"Failed to get verified channels from DB: {e}")
            return []

    async def generate_custom_playlist(self, channel_urls: list, name: str = "Custom") -> str:
        lines = [
            "#EXTM3U",
            f'#PLAYLIST: CODETV - {name}',
            f'#GENERATED: {datetime.utcnow().isoformat()}Z',
            "",
        ]

        for ch in channel_urls:
            name_str = ch.get("name", "Channel")
            url = ch.get("url", "")
            tvg_id = ch.get("tvg_id", "")
            logo = ch.get("logo", "")
            group = ch.get("category", "")

            extinf = f'#EXTINF:-1'
            if tvg_id:
                extinf += f' tvg-id="{tvg_id}"'
            if logo:
                extinf += f' tvg-logo="{logo}"'
            if group:
                extinf += f' group-title="{group}"'
            extinf += f',{name_str}'

            lines.append(extinf)
            lines.append(url)
            lines.append("")

        return "\n".join(lines)

    def _error_playlist(self, reason: str) -> str:
        return "\n".join([
            "#EXTM3U",
            "#PLAYLIST: ERROR",
            f"#ERROR: {reason}",
            "",
        ])

    async def close(self):
        await close_session()

<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('iptv:sync --source=iptv-org')->dailyAt('03:00');
Schedule::command('iptv:verify --limit=200')->everySixHours();
Schedule::command('iptv:sync-uganda')->twiceDaily(6, 18);
Schedule::command('iptv:sync-all-sources')->twiceDaily(4, 16);
Schedule::command('iptv:validate-uganda --async')->everyThreeHours();
Schedule::command('iptv:report-uganda')->dailyAt('07:00');

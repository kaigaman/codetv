<?php
$ch = App\Models\Channel::where("source","world-ip-tv")->orderBy("name")->limit(30)->pluck("name");
foreach ($ch as $c) { echo $c . "\n"; }

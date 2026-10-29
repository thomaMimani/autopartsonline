<?php

use Illuminate\Support\Facades\Schedule;

// Monthly re-fetch of article details and fitment older than 30 days (diagram 8).
Schedule::command('catalog:refresh-fitment')->monthlyOn(3, '03:17');

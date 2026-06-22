<?php

return [
    'name' => env('RELEASE_NAME', 'BantuDelice Production'),
    'version' => env('RELEASE_VERSION', '2026.03.23'),
    'frozen' => (bool) env('RELEASE_FROZEN', true),
    'locked_at' => env('RELEASE_LOCKED_AT', '2026-03-23 19:00:00'),
    'owner' => env('RELEASE_OWNER', 'Platform Team'),
];

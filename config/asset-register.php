<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('ASSET_REGISTER_ENABLED', true),
    'demo_command_allowed' => (bool) env('ASSET_REGISTER_DEMO_COMMAND_ALLOWED', true),
];

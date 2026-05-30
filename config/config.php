<?php

return [
  'name' => 'ShiftGenerator',
  'holiday' => [
    'holiday_api_key' => env('HOLIDAY_API_KEY')
  ],
  'back_home_route' => 'apps.index',
  'hooks' => [
    'enabled' => env('SHIFTGENERATOR_HOOK_ENABLED', false),
    'service' => \Modules\CoreUI\Services\UIService::class,
  ]
];
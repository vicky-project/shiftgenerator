<?php

return [
  'name' => 'ShiftGenerator',
  'holiday' => [
    'holiday_api_key' => env('HOLIDAY_API_KEY')
  ],
  'back_home_url' => route('apps.index')
];
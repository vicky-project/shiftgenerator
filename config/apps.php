<?php

return [
  'id' => 'rostergenerator',
  'name' => 'Roster Shift Generator',
  'description' => 'Kelola karyawan, atur pola shift, dan buat roster kerja otomatis berdasarkan siklus kerja-cuti.',
  'icon_emoji' => '🗓️',
  'render_type' => 'iframe',
  'render_config' => [
    'url' => rtrim(env('APP_URL'), '/') . '/apps/shift'
  ]
];
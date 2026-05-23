<?php

namespace Modules\ShiftGenerator\Enums;

enum ShiftType: string
{
  case Day = 'Day';
  case Night = 'Night';
  case Off = 'Off';

    public static function values(): array
    {
      return array_column(self::cases(), 'value');
    }
}
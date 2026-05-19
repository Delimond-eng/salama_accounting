<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class DateTimeFormat
{
    public const DATETIME = 'd/m/Y H:i';

    public const DATE = 'd/m/Y';

    public static function format(mixed $value, string $format = self::DATETIME): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format($format);
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return is_string($value) ? $value : null;
        }
    }
}

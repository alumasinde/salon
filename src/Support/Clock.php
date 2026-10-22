<?php

declare(strict_types=1);

namespace Salon\Support;

/** Converts between the "8:00 am" labels clients see and 24-hour "08:00" values. */
final class Clock
{
    public static function to24(string $label): ?string
    {
        $ts = strtotime(trim($label));
        return $ts === false ? null : date('H:i', $ts);
    }

    public static function label(string $time24): string
    {
        $ts = strtotime($time24);
        if ($ts === false) {
            return $time24;
        }
        $hour = (int) date('G', $ts);
        $suffix = $hour >= 12 ? 'pm' : 'am';
        $hour12 = $hour % 12 === 0 ? 12 : $hour % 12;
        return $hour12 . ':' . date('i', $ts) . ' ' . $suffix;
    }

    /** @return string[] slot labels between two HH:MM:SS values at $step minutes */
    public static function slots(string $opens, string $closes, int $step = 30, int $fromMinute = 0): array
    {
        $step = max(5, min(120, $step));
        $stop = self::minutesOfDay($closes);
        $slots = [];
        for ($m = self::minutesOfDay($opens); $m < $stop; $m += $step) {
            if ($m < $fromMinute) {
                continue;
            }
            $slots[] = self::label(sprintf('%02d:%02d', intdiv($m, 60), $m % 60));
        }
        return $slots;
    }

    public static function minutesOfDay(string $time24): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time24, 0, 5)));
        return $h * 60 + $m;
    }
}

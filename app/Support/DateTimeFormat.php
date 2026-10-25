<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The one place that turns stored instants into what the user reads, and back.
 *
 * Timestamps stay in the database in the application timezone (`app.timezone`, UTC);
 * what a person sees is rendered in the display timezone (`app.display_timezone`,
 * `APP_DISPLAY_TIMEZONE`, default UTC). Dates with a time read
 * `21 Aug 2026, 14:00`. The frontend counterpart is `DEFAULT_DATETIME_FORMAT` in
 * resources/js/lib/resourceCell.ts.
 */
final class DateTimeFormat {
    public const string DISPLAY = 'j M Y, H:i';

    public const string DISPLAY_DATE = 'j M Y';

    /** The value format of an `<input type="datetime-local">`. */
    public const string INPUT = 'Y-m-d\TH:i';

    public static function timezone(): string {
        return (string) config('app.display_timezone');
    }

    public static function display(CarbonInterface $date): string {
        return self::inDisplayTimezone($date)->format(self::DISPLAY);
    }

    public static function displayOrNull(?CarbonInterface $date): ?string {
        return $date === null ? null : self::display($date);
    }

    public static function displayDate(CarbonInterface $date): string {
        return self::inDisplayTimezone($date)->format(self::DISPLAY_DATE);
    }

    public static function displayDateOrNull(?CarbonInterface $date): ?string {
        return $date === null ? null : self::displayDate($date);
    }

    /** A stored instant as the value of a `datetime-local` input (display timezone). */
    public static function toInput(CarbonInterface $date): string {
        return self::inDisplayTimezone($date)->format(self::INPUT);
    }

    /**
     * A `datetime-local` value typed by the user (wall-clock time in the display
     * timezone) as an instant in the application timezone, ready to be stored.
     */
    public static function fromInput(string $value): Carbon {
        return Carbon::parse($value, self::timezone())->setTimezone((string) config('app.timezone'));
    }

    private static function inDisplayTimezone(CarbonInterface $date): CarbonInterface {
        return $date->copy()->setTimezone(self::timezone());
    }
}

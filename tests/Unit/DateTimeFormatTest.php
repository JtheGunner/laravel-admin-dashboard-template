<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DateTimeFormat;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateTimeFormatTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        config(['app.display_timezone' => 'Europe/Zurich']);
    }

    public function test_the_display_format_reads_day_short_month_year_and_hours_minutes() {
        $this->assertSame('21 Aug 2026, 14:00', DateTimeFormat::display(Carbon::parse('2026-08-21 12:00:30', 'UTC')));
    }

    public function test_a_utc_instant_is_shown_in_summer_and_winter_time() {
        $this->assertSame('21 Aug 2026, 14:00', DateTimeFormat::display(Carbon::parse('2026-08-21 12:00:00', 'UTC')));
        $this->assertSame('5 Jan 2026, 10:05', DateTimeFormat::display(Carbon::parse('2026-01-05 09:05:00', 'UTC')));
    }

    public function test_the_display_timezone_follows_the_configuration() {
        config(['app.display_timezone' => 'America/New_York']);

        $this->assertSame('21 Aug 2026, 08:00', DateTimeFormat::display(Carbon::parse('2026-08-21 12:00:00', 'UTC')));
    }

    public function test_the_date_only_variant_uses_the_display_timezone() {
        $this->assertSame('22 Aug 2026', DateTimeFormat::displayDate(Carbon::parse('2026-08-21 23:30:00', 'UTC')));
    }

    public function test_nullable_variants_pass_null_through() {
        $this->assertNull(DateTimeFormat::displayOrNull(null));
        $this->assertNull(DateTimeFormat::displayDateOrNull(null));
    }

    public function test_a_datetime_local_input_is_read_in_the_display_timezone_and_stored_as_utc() {
        $stored = DateTimeFormat::fromInput('2026-08-21T14:00');

        $this->assertSame('2026-08-21 12:00', $stored->format('Y-m-d H:i'));
        $this->assertSame(config('app.timezone'), $stored->getTimezone()->getName());
    }

    public function test_the_input_value_round_trips_through_storage() {
        $this->assertSame('2026-01-05T09:05', DateTimeFormat::toInput(DateTimeFormat::fromInput('2026-01-05T09:05')));
    }
}

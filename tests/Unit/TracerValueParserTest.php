<?php

namespace Tests\Unit;

use App\Support\TracerValueParser;
use PHPUnit\Framework\TestCase;

class TracerValueParserTest extends TestCase
{
    public function test_date_reads_an_excel_serial_number(): void
    {
        // 45884.4375 is 2025-08-15 10:30 in Excel's 1900 date system.
        $this->assertSame('2025-08-15 10:30:00', TracerValueParser::date('45884.4375'));
    }

    public function test_date_reads_indonesian_day_first_text(): void
    {
        $this->assertSame('2025-08-15 00:00:00', TracerValueParser::date('15/08/2025'));
        $this->assertSame('2025-08-15 10:30:00', TracerValueParser::date('15-08-2025 10:30'));
    }

    public function test_date_reads_iso_text(): void
    {
        $this->assertSame('2025-08-15 10:30:00', TracerValueParser::date('2025-08-15 10:30:00'));
    }

    public function test_date_rejects_blank_and_impossible_dates(): void
    {
        $this->assertNull(TracerValueParser::date(''));
        $this->assertNull(TracerValueParser::date(null));
        $this->assertNull(TracerValueParser::date('31/02/2025'));
    }
}

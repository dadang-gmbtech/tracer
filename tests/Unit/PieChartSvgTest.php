<?php

namespace Tests\Unit;

use App\Support\PieChartSvg;
use PHPUnit\Framework\TestCase;

class PieChartSvgTest extends TestCase
{
    public function test_slices_percentages_add_up_to_100(): void
    {
        $slices = PieChartSvg::slices(
            ['Bekerja' => 70, 'Wiraswasta' => 14, 'Studi Lanjut' => 13, 'Belum Bekerja' => 3],
            ['Bekerja' => '#1d4ed8'],
        );

        $this->assertCount(4, $slices);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($slices, 'percent')), 0.2);
    }

    public function test_zero_and_negative_values_are_skipped(): void
    {
        $slices = PieChartSvg::slices(['A' => 10, 'B' => 0, 'C' => -5], ['A' => '#fff']);

        $this->assertCount(1, $slices);
        $this->assertSame('A', $slices[0]['label']);
    }

    public function test_empty_values_returns_no_slices(): void
    {
        $this->assertSame([], PieChartSvg::slices([], []));
    }

    public function test_all_zero_values_returns_no_slices(): void
    {
        $this->assertSame([], PieChartSvg::slices(['A' => 0, 'B' => 0], []));
    }

    public function test_a_single_full_share_slice_draws_a_closed_circle_path(): void
    {
        $slices = PieChartSvg::slices(['A' => 5], ['A' => '#000']);

        $this->assertCount(1, $slices);
        $this->assertSame(100.0, $slices[0]['percent']);
        // The 100%-slice special case draws two semicircle arcs, not a single arc.
        $this->assertSame(2, substr_count($slices[0]['path'], ' A '));
    }

    public function test_unlisted_label_falls_back_to_a_default_color(): void
    {
        $slices = PieChartSvg::slices(['A' => 1, 'B' => 1], ['A' => '#111111']);

        $this->assertSame('#111111', $slices[0]['color']);
        $this->assertSame('#9ca3af', $slices[1]['color']);
    }
}

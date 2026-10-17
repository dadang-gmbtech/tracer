<?php

namespace App\Support;

/**
 * Computes SVG <path> data for a pie chart's slices, for views rendered
 * through dompdf (AutoReportController's PDF report) — dompdf can't run
 * JavaScript, so a Chart.js canvas isn't an option there; dompdf does
 * render basic inline SVG (paths, arcs), which this targets directly.
 */
class PieChartSvg
{
    /**
     * @param  array<string, int|float>  $values  label => value; a label with a zero/negative value or an empty $values is simply skipped
     * @param  array<string, string>  $colors  label => hex color, falls back to a muted gray for any label not listed
     * @return list<array{label: string, value: float, percent: float, color: string, path: string}>
     */
    public static function slices(array $values, array $colors, float $radius = 60, float $cx = 60, float $cy = 60): array
    {
        $total = array_sum($values);

        if ($total <= 0) {
            return [];
        }

        $slices = [];
        $angle = -90.0; // start at 12 o'clock, sweeping clockwise

        foreach ($values as $label => $value) {
            if ($value <= 0) {
                continue;
            }

            $fraction = $value / $total;
            $color = $colors[$label] ?? '#9ca3af';

            $slices[] = [
                'label' => $label,
                'value' => (float) $value,
                'percent' => round($fraction * 100, 1),
                'color' => $color,
                'path' => self::slicePath($fraction, $angle, $radius, $cx, $cy),
            ];

            $angle += $fraction * 360;
        }

        return $slices;
    }

    private static function slicePath(float $fraction, float $startAngle, float $radius, float $cx, float $cy): string
    {
        // A single 100%-share slice can't be drawn as one arc (the start and
        // end point coincide), so draw it as two half-circle arcs instead.
        if ($fraction >= 0.9999) {
            return sprintf(
                'M %F %F A %F %F 0 1 1 %F %F A %F %F 0 1 1 %F %F Z',
                $cx + $radius, $cy, $radius, $radius, $cx - $radius, $cy, $radius, $radius, $cx + $radius, $cy,
            );
        }

        $endAngle = $startAngle + $fraction * 360;
        $x1 = $cx + $radius * cos(deg2rad($startAngle));
        $y1 = $cy + $radius * sin(deg2rad($startAngle));
        $x2 = $cx + $radius * cos(deg2rad($endAngle));
        $y2 = $cy + $radius * sin(deg2rad($endAngle));
        $largeArc = $fraction > 0.5 ? 1 : 0;

        return sprintf(
            'M %F %F L %F %F A %F %F 0 %d 1 %F %F Z',
            $cx, $cy, $x1, $y1, $radius, $radius, $largeArc, $x2, $y2,
        );
    }
}

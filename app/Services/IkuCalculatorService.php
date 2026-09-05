<?php

namespace App\Services;

use App\Models\TracerResponse;
use App\Models\UmpSalary;

/**
 * Implements the IKU-2 weighted-bobot formula from "Rumus IKU.pdf" (Diktisaintek
 * Berdampak): lulusan yang bekerja / berwirausaha / melanjutkan studi dalam 1 tahun
 * setelah kelulusan, dibobot berdasar masa tunggu dan gaji vs 1,2x UMP.
 *
 * Assumption: the source form has no separate "sudah bekerja sebelum lulus" flag,
 * so pre-graduation employment (kriteria e/f in the PDF) is treated the same as a
 * post-graduation hire with masa tunggu 0 — it naturally lands in the "<6 bulan"
 * bucket, which produces the same bobot the PDF assigns those cases.
 */
class IkuCalculatorService
{
    /**
     * Weight (k) contributed by a single tracer response, 0 if the response does
     * not count toward IKU-2 (still bekerja/belum lulus/mencari kerja) or the
     * outcome fell outside the 1-year window (masa tunggu >= 12 bulan).
     */
    public function bobot(TracerResponse $response, ?UmpSalary $ump): float
    {
        return match ((int) $response->f8) {
            TracerResponse::STATUS_BEKERJA => $this->bobotBekerja($response, $ump),
            TracerResponse::STATUS_WIRASWASTA => $this->bobotWiraswasta($response, $ump),
            TracerResponse::STATUS_MELANJUTKAN_STUDI => $this->bobotMelanjutkanStudi($response),
            default => 0.0,
        };
    }

    public function isRespondenBerhasil(TracerResponse $response): bool
    {
        return in_array((int) $response->f8, [
            TracerResponse::STATUS_BEKERJA,
            TracerResponse::STATUS_WIRASWASTA,
            TracerResponse::STATUS_MELANJUTKAN_STUDI,
        ], true);
    }

    protected function bobotBekerja(TracerResponse $response, ?UmpSalary $ump): float
    {
        $masaTunggu = $response->f502;

        if ($masaTunggu === null || $masaTunggu >= 12) {
            return 0.0;
        }

        $gajiCukup = $this->gajiMemenuhiUmp($response->f505, $ump);

        if ($masaTunggu < 6) {
            return $gajiCukup ? 1.0 : 0.6;
        }

        return $gajiCukup ? 0.8 : 0.6;
    }

    /**
     * f5c: 1=Founder, 2=Co-Founder, 3=Staff, 4=Freelance.
     * The PDF's bobot table only defines Founder/Co-Founder and Freelancer rows;
     * "Staff" (an employee-like role within one's own business) falls back to the
     * regular employee bobot scale.
     */
    protected function bobotWiraswasta(TracerResponse $response, ?UmpSalary $ump): float
    {
        $masaTunggu = $response->f502;

        if ($masaTunggu === null || $masaTunggu >= 12) {
            return 0.0;
        }

        $gajiCukup = $this->gajiMemenuhiUmp($response->f505, $ump);
        $posisi = (int) $response->f5c;

        if (in_array($posisi, [1, 2], true)) {
            if ($masaTunggu < 6) {
                return $gajiCukup ? 1.2 : 0.8;
            }

            return $gajiCukup ? 1.0 : 0.6;
        }

        if ($posisi === 4) {
            if ($masaTunggu < 6) {
                return $gajiCukup ? 0.5 : 0.3;
            }

            return $gajiCukup ? 0.4 : 0.2;
        }

        return $this->bobotBekerja($response, $ump);
    }

    protected function bobotMelanjutkanStudi(TracerResponse $response): float
    {
        return 0.6;
    }

    protected function gajiMemenuhiUmp(mixed $gaji, ?UmpSalary $ump): bool
    {
        if ($gaji === null || $ump === null) {
            return false;
        }

        return (float) $gaji > 1.2 * (float) $ump->amount;
    }
}

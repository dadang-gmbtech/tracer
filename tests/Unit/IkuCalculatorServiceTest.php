<?php

namespace Tests\Unit;

use App\Models\TracerResponse;
use App\Models\UmpSalary;
use App\Services\IkuCalculatorService;
use Tests\TestCase;

class IkuCalculatorServiceTest extends TestCase
{
    private IkuCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IkuCalculatorService;
    }

    private function ump(float $amount): UmpSalary
    {
        return new UmpSalary(['amount' => $amount]);
    }

    public function test_bekerja_under_6_months_with_high_salary_scores_full_bobot(): void
    {
        $response = new TracerResponse(['f8' => 1, 'f502' => 3, 'f505' => 5_000_000]);

        $this->assertSame(1.0, $this->service->bobot($response, $this->ump(2_000_000)));
    }

    public function test_bekerja_under_6_months_with_low_salary_scores_point_six(): void
    {
        $response = new TracerResponse(['f8' => 1, 'f502' => 3, 'f505' => 1_000_000]);

        $this->assertSame(0.6, $this->service->bobot($response, $this->ump(2_000_000)));
    }

    public function test_bekerja_between_6_and_12_months_with_high_salary_scores_point_eight(): void
    {
        $response = new TracerResponse(['f8' => 1, 'f502' => 8, 'f505' => 5_000_000]);

        $this->assertSame(0.8, $this->service->bobot($response, $this->ump(2_000_000)));
    }

    public function test_bekerja_after_12_months_scores_zero(): void
    {
        $response = new TracerResponse(['f8' => 1, 'f502' => 13, 'f505' => 5_000_000]);

        $this->assertSame(0.0, $this->service->bobot($response, $this->ump(2_000_000)));
    }

    public function test_founder_under_6_months_with_high_income_scores_1_2(): void
    {
        $response = new TracerResponse(['f8' => 3, 'f502' => 2, 'f505' => 5_000_000, 'f5c' => 1]);

        $this->assertSame(1.2, $this->service->bobot($response, $this->ump(2_000_000)));
    }

    public function test_freelancer_under_6_months_with_low_income_scores_0_3(): void
    {
        $response = new TracerResponse(['f8' => 3, 'f502' => 2, 'f505' => 500_000, 'f5c' => 4]);

        $this->assertSame(0.3, $this->service->bobot($response, $this->ump(2_000_000)));
    }

    public function test_melanjutkan_studi_always_scores_point_six(): void
    {
        $response = new TracerResponse(['f8' => 4]);

        $this->assertSame(0.6, $this->service->bobot($response, null));
    }

    public function test_mencari_kerja_scores_zero_but_still_counts_as_a_respondent(): void
    {
        $response = new TracerResponse(['f8' => 5]);

        $this->assertSame(0.0, $this->service->bobot($response, null));
        $this->assertFalse($this->service->isRespondenBerhasil($response));
    }
}

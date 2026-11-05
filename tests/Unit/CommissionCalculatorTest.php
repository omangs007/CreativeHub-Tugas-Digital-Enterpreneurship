<?php

namespace Tests\Unit;

use App\Support\CommissionCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    public function test_splits_total_into_commission_and_freelancer_share(): void
    {
        $this->assertSame(['rate_bps' => 1000, 'commission' => 50_000, 'freelancer' => 450_000], CommissionCalculator::split(500_000, 1000));
    }

    public function test_commission_is_rounded_down_and_parts_always_sum_to_total(): void
    {
        foreach ([1, 99, 10_001, 1_234_567, 999_999_999] as $total) {
            foreach ([0, 750, 1000, 1250, 10_000] as $bps) {
                $split = CommissionCalculator::split($total, $bps);

                $this->assertSame($total, $split['commission'] + $split['freelancer']);
                $this->assertSame(intdiv($total * $bps, 10_000), $split['commission']);
            }
        }
    }

    public function test_rejects_invalid_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommissionCalculator::split(0, 1000);
    }

    public function test_rejects_rate_above_hundred_percent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommissionCalculator::split(100_000, 10_001);
    }
}

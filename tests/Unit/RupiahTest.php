<?php

namespace Tests\Unit;

use App\Support\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    public function test_formats_amount_as_rupiah_with_dot_separator(): void
    {
        $this->assertSame('Rp0', Rupiah::format(0));
        $this->assertSame('Rp5.000', Rupiah::format(5_000));
        $this->assertSame('Rp150.000', Rupiah::format(150_000));
        $this->assertSame('Rp1.250.000', Rupiah::format(1_250_000));
    }
}

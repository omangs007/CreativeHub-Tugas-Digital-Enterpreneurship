<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\FinancialReport;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15 10:00:00');
        $this->admin = User::factory()->admin()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function payment(PaymentStatus $status, int $amount, string $createdAt, ?string $paidAt = null, string $title = 'Pesanan'): Payment
    {
        $order = Order::factory()->total($amount)->create(['title' => $title]);

        return Payment::factory()->for($order)->create([
            'status' => $status,
            'created_at' => $createdAt,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedNovember(): void
    {
        $this->payment(PaymentStatus::Success, 100_000, '2026-11-01 00:00:00', '2026-11-01 00:00:00', 'Awal Bulan');
        $this->payment(PaymentStatus::Success, 200_000, '2026-11-30 23:00:00', '2026-11-30 23:59:59', 'Akhir Bulan');
        $this->payment(PaymentStatus::Success, 400_000, '2026-10-30 09:00:00', '2026-11-02 08:00:00', 'Dibuat Oktober Dibayar November');
        $this->payment(PaymentStatus::Success, 999_000, '2026-10-31 23:00:00', '2026-10-31 23:59:59', 'Oktober Saja');
        $this->payment(PaymentStatus::Success, 888_000, '2026-12-01 00:00:00', '2026-12-01 00:00:00', 'Desember Saja');
        $this->payment(PaymentStatus::Pending, 50_000, '2026-11-10 12:00:00', null, 'Masih Pending');
        $this->payment(PaymentStatus::Failed, 70_000, '2026-11-11 12:00:00', null, 'Gagal Bayar');
        $this->payment(PaymentStatus::Expired, 80_000, '2026-11-12 12:00:00', null, 'Kedaluwarsa');
        $this->payment(PaymentStatus::Cancelled, 90_000, '2026-11-13 12:00:00', null, 'Dibatalkan');
    }

    public function test_only_admin_can_open_report(): void
    {
        $this->get('/admin/financial-reports')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->freelancer()->create())->get('/admin/financial-reports')->assertForbidden();
        $this->actingAs(User::factory()->customer()->create())->get('/admin/financial-reports/pdf')->assertForbidden();

        $this->admin->forceFill(['is_active' => false])->save();
        $this->actingAs($this->admin->fresh())->get('/admin/financial-reports')->assertRedirect(route('login'));
    }

    public function test_monthly_summary_counts_only_successful_payments_as_revenue(): void
    {
        config(['creativehub.commission_rate_bps' => 1000]);
        $this->seedNovember();

        $summary = FinancialReport::fromInput(['preset' => 'this_month'])->summary();

        $this->assertSame(3, $summary['success_count']);
        $this->assertSame(700_000, $summary['revenue']);
        $this->assertSame(70_000, $summary['commission']);
        $this->assertSame(630_000, $summary['freelancer']);
        $this->assertSame($summary['revenue'], $summary['commission'] + $summary['freelancer']);
        $this->assertSame(1, $summary['pending_count']);
        $this->assertSame(50_000, $summary['pending_amount']);
        $this->assertSame(1, $summary['failed_count']);
        $this->assertSame(1, $summary['expired_count']);
        $this->assertSame(1, $summary['cancelled_count']);
        $this->assertSame(7, $summary['total_count']);
    }

    public function test_page_shows_filtered_table_and_matching_totals(): void
    {
        $this->seedNovember();

        $this->actingAs($this->admin)->get('/admin/financial-reports?preset=month&month=2026-11')
            ->assertOk()
            ->assertSee('01 November 2026 – 30 November 2026')
            ->assertSee('Rp700.000')
            ->assertSee('Rp70.000')
            ->assertSee('Rp630.000')
            ->assertSee('Awal Bulan')
            ->assertSee('Akhir Bulan')
            ->assertSee('Dibuat Oktober Dibayar November')
            ->assertSee('Masih Pending')
            ->assertDontSee('Oktober Saja')
            ->assertDontSee('Desember Saja')
            ->assertSee(route('admin.financial-reports.pdf', ['preset' => 'month', 'month' => '2026-11']));
    }

    public function test_presets_resolve_expected_ranges(): void
    {
        $this->assertSame(['2026-11-15', '2026-11-15'], $this->range('today'));
        $this->assertSame(['2026-11-14', '2026-11-14'], $this->range('yesterday'));
        $this->assertSame(['2026-11-09', '2026-11-15'], $this->range('last_7_days'));
        $this->assertSame(['2026-11-01', '2026-11-30'], $this->range('this_month'));
        $this->assertSame(['2026-02-01', '2026-02-28'], $this->range('month', ['month' => '2026-02']));
        $this->assertSame(['2026-11-03', '2026-11-05'], $this->range('custom', ['start' => '2026-11-03', 'end' => '2026-11-05']));

        $report = FinancialReport::fromInput(['preset' => 'today']);
        $this->assertSame('00:00:00', $report->start->format('H:i:s'));
        $this->assertSame('23:59:59', $report->end->format('H:i:s'));
    }

    public function test_last_7_days_includes_boundaries(): void
    {
        $this->payment(PaymentStatus::Success, 10_000, '2026-11-09 00:00:00', '2026-11-09 00:00:00');
        $this->payment(PaymentStatus::Success, 20_000, '2026-11-15 09:59:00', '2026-11-15 09:59:00');
        $this->payment(PaymentStatus::Success, 40_000, '2026-11-08 23:59:59', '2026-11-08 23:59:59');

        $this->assertSame(30_000, FinancialReport::fromInput(['preset' => 'last_7_days'])->summary()['revenue']);
        $this->assertSame(20_000, FinancialReport::fromInput(['preset' => 'today'])->summary()['revenue']);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        foreach ([
            ['preset' => 'custom', 'start' => '2026-11-10', 'end' => '2026-11-01'],
            ['preset' => 'custom', 'start' => '2025-01-01', 'end' => '2026-11-01'],
            ['preset' => 'custom'],
            ['preset' => 'month', 'month' => '2026-13'],
        ] as $input) {
            try {
                FinancialReport::fromInput($input);
                $this->fail('Input seharusnya ditolak: '.json_encode($input));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->actingAs($this->admin)->from('/admin/financial-reports')->get('/admin/financial-reports?preset=abc')->assertSessionHasErrors('preset');
    }

    public function test_pdf_uses_same_filter_and_numbers(): void
    {
        $this->seedNovember();

        $response = $this->actingAs($this->admin)->get('/admin/financial-reports/pdf?preset=month&month=2026-11');
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('laporan-keuangan-20261101-20261130.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Template PDF dirender dari data yang sama dengan halaman.
        $report = FinancialReport::fromInput(['preset' => 'month', 'month' => '2026-11']);
        $html = view('admin.financial-report.pdf', [
            'report' => $report,
            'summary' => $report->summary(),
            'transactions' => $report->transactions(),
            'printedAt' => now(),
            'printedBy' => $this->admin->name,
        ])->render();

        $this->assertStringContainsString('Rp700.000', $html);
        $this->assertStringContainsString('Rp630.000', $html);
        $this->assertStringContainsString('01 November 2026 – 30 November 2026', $html);
        $this->assertStringNotContainsString('Oktober Saja', $html);
    }

    public function test_admin_panel_links_to_report(): void
    {
        Filament::setCurrentPanel('admin');

        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee(route('admin.financial-reports'), false);
    }

    /**
     * @param  array<string, string>  $extra
     * @return array{0: string, 1: string}
     */
    private function range(string $preset, array $extra = []): array
    {
        $report = FinancialReport::fromInput(['preset' => $preset, ...$extra]);

        return [$report->start->toDateString(), $report->end->toDateString()];
    }
}

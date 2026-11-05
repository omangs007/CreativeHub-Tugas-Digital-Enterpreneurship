<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Sumber tunggal angka laporan keuangan (halaman, cetak, dan PDF memakai kelas ini).
 *
 * Definisi:
 * - Tanggal transaksi = tanggal bayar untuk transaksi berhasil, tanggal dibuat untuk lainnya
 *   (zona waktu aplikasi Asia/Jakarta, batas tanggal inklusif).
 * - Omzet terealisasi = Σ nominal pembayaran BERHASIL dalam periode.
 * - Komisi marketplace / pendapatan freelancer = Σ snapshot pada pembayaran berhasil.
 * - Pending, gagal, kedaluwarsa, dan dibatalkan dihitung terpisah, tidak masuk omzet.
 */
final class FinancialReport
{
    public const PRESETS = [
        'today' => 'Hari ini',
        'yesterday' => 'Kemarin',
        'last_7_days' => '7 hari terakhir',
        'this_month' => 'Bulan ini',
        'month' => 'Bulan tertentu',
        'custom' => 'Rentang tanggal',
    ];

    public const MAX_RANGE_DAYS = 366;

    private const DATE_EXPRESSION = 'COALESCE(payments.paid_at, payments.created_at)';

    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string $preset,
    ) {}

    /**
     * @param  array{preset?: ?string, month?: ?string, start?: ?string, end?: ?string}  $input
     *
     * @throws ValidationException
     */
    public static function fromInput(array $input): self
    {
        $preset = $input['preset'] ?? 'this_month';
        $today = CarbonImmutable::now()->startOfDay();

        [$start, $end] = match ($preset) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'last_7_days' => [$today->subDays(6), $today],
            'this_month' => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
            'month' => self::monthRange($input['month'] ?? null),
            'custom' => self::customRange($input['start'] ?? null, $input['end'] ?? null),
            default => throw ValidationException::withMessages(['preset' => __('Preset periode tidak dikenal.')]),
        };

        return new self($start->startOfDay(), $end->endOfDay(), $preset);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function monthRange(?string $month): array
    {
        if (! $month || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw ValidationException::withMessages(['month' => __('Pilih bulan dengan format yang benar.')]);
        }

        $first = CarbonImmutable::createFromFormat('!Y-m', $month);

        return [$first->startOfMonth(), $first->endOfMonth()];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function customRange(?string $start, ?string $end): array
    {
        $parse = fn (?string $value) => $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            ? CarbonImmutable::createFromFormat('!Y-m-d', $value)
            : null;

        $from = $parse($start);
        $to = $parse($end);

        if (! $from || ! $to) {
            throw ValidationException::withMessages(['start' => __('Isi tanggal mulai dan tanggal akhir.')]);
        }

        if ($to->lt($from)) {
            throw ValidationException::withMessages(['end' => __('Tanggal akhir tidak boleh sebelum tanggal mulai.')]);
        }

        if ($from->diffInDays($to) >= self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages(['end' => __('Rentang maksimal :n hari.', ['n' => self::MAX_RANGE_DAYS])]);
        }

        return [$from, $to];
    }

    public function periodLabel(): string
    {
        $start = $this->start->translatedFormat('d F Y');
        $end = $this->end->translatedFormat('d F Y');

        return $start === $end ? $start : "{$start} – {$end}";
    }

    /**
     * Parameter URL untuk tautan cetak/PDF dengan filter yang sama.
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        return match ($this->preset) {
            'month' => ['preset' => 'month', 'month' => $this->start->format('Y-m')],
            'custom' => ['preset' => 'custom', 'start' => $this->start->toDateString(), 'end' => $this->end->toDateString()],
            default => ['preset' => $this->preset],
        };
    }

    /**
     * @return Builder<Payment>
     */
    private function inPeriod(): Builder
    {
        return Payment::query()->whereRaw(self::DATE_EXPRESSION.' BETWEEN ? AND ?', [
            $this->start->toDateTimeString(),
            $this->end->toDateTimeString(),
        ]);
    }

    /**
     * @return array{success_count: int, revenue: int, commission: int, freelancer: int, pending_count: int, pending_amount: int, failed_count: int, expired_count: int, cancelled_count: int, total_count: int}
     */
    public function summary(): array
    {
        $rows = $this->inPeriod()
            ->selectRaw('status, count(*) as total, coalesce(sum(amount), 0) as amount, coalesce(sum(commission_amount), 0) as commission, coalesce(sum(freelancer_amount), 0) as freelancer')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->getRawOriginal('status'));

        $count = fn (PaymentStatus $s) => (int) ($rows[$s->value]->total ?? 0);
        $sum = fn (PaymentStatus $s, string $column) => (int) ($rows[$s->value]->{$column} ?? 0);

        return [
            'success_count' => $count(PaymentStatus::Success),
            'revenue' => $sum(PaymentStatus::Success, 'amount'),
            'commission' => $sum(PaymentStatus::Success, 'commission'),
            'freelancer' => $sum(PaymentStatus::Success, 'freelancer'),
            'pending_count' => $count(PaymentStatus::Pending),
            'pending_amount' => $sum(PaymentStatus::Pending, 'amount'),
            'failed_count' => $count(PaymentStatus::Failed),
            'expired_count' => $count(PaymentStatus::Expired),
            'cancelled_count' => $count(PaymentStatus::Cancelled),
            'total_count' => (int) $rows->sum('total'),
        ];
    }

    /**
     * Omzet terealisasi per hari (tanggal => Rupiah), termasuk hari tanpa transaksi.
     *
     * @return array<string, int>
     */
    public function dailyRevenue(): array
    {
        $sums = $this->inPeriod()
            ->where('status', PaymentStatus::Success)
            ->selectRaw('date(paid_at) as day, sum(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];
        for ($day = $this->start; $day->lte($this->end); $day = $day->addDay()) {
            $days[$day->toDateString()] = (int) ($sums[$day->toDateString()] ?? 0);
        }

        return $days;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function transactions(): Collection
    {
        return $this->inPeriod()
            ->with(['order.freelancer', 'order.service', 'customer'])
            ->orderByRaw(self::DATE_EXPRESSION.' asc')
            ->orderBy('id')
            ->get();
    }
}

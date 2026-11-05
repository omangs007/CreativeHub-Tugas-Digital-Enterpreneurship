<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan {{ $report->periodLabel() }}</title>
    <style>
        @page { margin: 22px 26px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #10152d; margin: 0; }
        .head { width: 100%; border-bottom: 2px solid #10152d; padding-bottom: 8px; margin-bottom: 10px; }
        .head td { vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; }
        .brand span { color: #7c3aed; }
        .muted { color: #4b5888; }
        .right { text-align: right; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 10px; }
        .cards td { border: 1px solid #c9d1e6; padding: 6px 8px; width: 25%; }
        .label { font-size: 8px; text-transform: uppercase; letter-spacing: .5px; color: #6b7aa8; }
        .value { font-size: 13px; font-weight: bold; margin-top: 2px; }
        table.tx { width: 100%; border-collapse: collapse; }
        table.tx th { background: #f4f6fb; text-align: left; font-size: 8px; text-transform: uppercase; color: #4b5888; padding: 5px 4px; border-bottom: 1px solid #c9d1e6; }
        table.tx td { padding: 4px; border-bottom: 1px solid #e6eaf4; }
        table.tx tfoot td { font-weight: bold; border-top: 2px solid #10152d; border-bottom: none; padding-top: 6px; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.5px; }
        .note { margin-top: 10px; font-size: 8px; color: #78350f; border: 1px dashed #d97706; background: #fffbeb; padding: 5px 7px; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="brand">Creative<span>Hub</span></div>
                <div class="muted">Laporan Keuangan Marketplace (transaksi simulasi)</div>
            </td>
            <td class="right">
                <div><strong>Periode:</strong> {{ $report->periodLabel() }}</div>
                <div class="muted">Dicetak {{ $printedAt->translatedFormat('d F Y, H:i') }} WIB oleh {{ $printedBy }}</div>
            </td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            <td><div class="label">Omzet terealisasi</div><div class="value">{{ \App\Support\Rupiah::format($summary['revenue']) }}</div><div class="muted">{{ $summary['success_count'] }} transaksi berhasil</div></td>
            <td><div class="label">Komisi marketplace</div><div class="value">{{ \App\Support\Rupiah::format($summary['commission']) }}</div></td>
            <td><div class="label">Pendapatan freelancer</div><div class="value">{{ \App\Support\Rupiah::format($summary['freelancer']) }}</div></td>
            <td><div class="label">Pending / Gagal / Kedaluwarsa / Batal</div><div class="value">{{ $summary['pending_count'] }} / {{ $summary['failed_count'] }} / {{ $summary['expired_count'] }} / {{ $summary['cancelled_count'] }}</div><div class="muted">pending {{ \App\Support\Rupiah::format($summary['pending_amount']) }} (belum terealisasi)</div></td>
        </tr>
    </table>

    <table class="tx">
        <thead>
            <tr>
                <th>No. Transaksi</th><th>Tanggal</th><th>Customer</th><th>Freelancer</th><th>Layanan / Pesanan</th><th>Metode</th><th>Status</th>
                <th class="right">Nilai</th><th class="right">Komisi</th><th class="right">Freelancer</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $payment)
                @php($isSuccess = $payment->status === \App\Enums\PaymentStatus::Success)
                <tr>
                    <td class="mono">{{ $payment->transaction_code }}</td>
                    <td>{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y H:i') }}</td>
                    <td>{{ $payment->customer->name }}</td>
                    <td>{{ $payment->order->freelancer->name }}</td>
                    <td>{{ $payment->order->title }}</td>
                    <td>{{ $payment->method->getLabel() }}</td>
                    <td>{{ $payment->status->getLabel() }}</td>
                    <td class="right">{{ \App\Support\Rupiah::format($payment->amount) }}</td>
                    <td class="right">{{ $isSuccess ? \App\Support\Rupiah::format($payment->commission_amount) : '-' }}</td>
                    <td class="right">{{ $isSuccess ? \App\Support\Rupiah::format($payment->freelancer_amount) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center; padding: 14px;" class="muted">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Total transaksi berhasil ({{ $summary['success_count'] }})</td>
                <td class="right">{{ \App\Support\Rupiah::format($summary['revenue']) }}</td>
                <td class="right">{{ \App\Support\Rupiah::format($summary['commission']) }}</td>
                <td class="right">{{ \App\Support\Rupiah::format($summary['freelancer']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="note">Seluruh transaksi adalah simulasi untuk demonstrasi tugas kuliah dan tidak memindahkan uang sungguhan. Omzet hanya menghitung transaksi berhasil; pembagian komisi hanya pencatatan.</div>
</body>
</html>

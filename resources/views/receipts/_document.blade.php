{{--
    Isi struk, dipakai halaman cetak (HTML) dan PDF (DomPDF).
    Memakai CSS sederhana (bukan Tailwind) agar hasil HTML dan PDF konsisten.
--}}
<div class="receipt">
    <table class="header">
        <tr>
            <td>
                <div class="brand">Creative<span>Hub</span></div>
                <div class="muted">Marketplace Jasa Kreatif</div>
                <div class="muted small">Proyek tugas kuliah — aplikasi lokal</div>
            </td>
            <td class="right">
                <div class="title">STRUK PEMBAYARAN</div>
                <div class="stamp">LUNAS (SIMULASI)</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr><th>No. Transaksi</th><td class="mono">{{ $payment->transaction_code }}</td></tr>
        <tr><th>No. Pesanan</th><td class="mono">{{ $order->order_number }}</td></tr>
        <tr><th>Tanggal Bayar</th><td>{{ $payment->paid_at->translatedFormat('d F Y, H:i') }} WIB</td></tr>
        <tr><th>Metode</th><td>{{ $payment->method->getLabel() }}</td></tr>
        <tr><th>Referensi</th><td class="mono">{{ $payment->provider_reference }}</td></tr>
        <tr><th>Status</th><td>{{ $payment->status->getLabel() }}</td></tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <div class="label">Customer</div>
                <div class="strong">{{ $order->customer->name }}</div>
                <div class="muted small">{{ $order->customer->email }}</div>
            </td>
            <td>
                <div class="label">Freelancer</div>
                <div class="strong">{{ $order->freelancer->name }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>Layanan / Paket</th><th class="right">Jumlah</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="strong">{{ $order->title }}</div>
                    <div class="muted small">
                        @if ($order->package_name)
                            Paket {{ $order->package_name }} ·
                        @elseif ($order->isFromProposal())
                            Penawaran proyek ·
                        @endif
                        {{ $order->delivery_days }} hari · {{ $order->revisions_allowed }} revisi
                    </div>
                </td>
                <td class="right">{{ \App\Support\Rupiah::format($order->subtotal_amount) }}</td>
            </tr>
            <tr><td class="muted">Biaya layanan</td><td class="right">{{ \App\Support\Rupiah::format(0) }}</td></tr>
        </tbody>
        <tfoot>
            <tr class="total"><td>Total Dibayar</td><td class="right">{{ \App\Support\Rupiah::format($payment->amount) }}</td></tr>
        </tfoot>
    </table>

    <table class="split">
        <tr><td colspan="2" class="label">Pembagian (pencatatan simulasi, bukan payout)</td></tr>
        <tr><td>Komisi marketplace ({{ rtrim(rtrim(number_format($order->commission_rate_bps / 100, 2, ',', '.'), '0'), ',') }}%)</td><td class="right">{{ \App\Support\Rupiah::format($payment->commission_amount) }}</td></tr>
        <tr><td>Bagian freelancer</td><td class="right">{{ \App\Support\Rupiah::format($payment->freelancer_amount) }}</td></tr>
    </table>

    <div class="notice">
        Transaksi ini adalah <strong>simulasi</strong> untuk demonstrasi tugas kuliah dan tidak memindahkan uang sungguhan.
        CreativeHub tidak terhubung ke bank, QRIS, atau e-wallet mana pun.
    </div>

    <div class="footer muted small">
        Dicetak {{ $printedAt->translatedFormat('d F Y, H:i') }} WIB · Data diambil dari database CreativeHub
    </div>
</div>

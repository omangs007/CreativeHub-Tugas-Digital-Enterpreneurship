<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan {{ $report->periodLabel() }} — CreativeHub Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-sheet { box-shadow: none !important; padding: 0 !important; }
            @page { size: A4 landscape; margin: 12mm; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-ink-900 antialiased">
    <header class="no-print border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <x-application-logo class="size-8" />
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">CreativeHub Admin</p>
                    <h1 class="text-lg font-bold">Laporan Keuangan</h1>
                </div>
            </div>
            <a href="{{ url('/admin') }}" class="text-sm font-semibold text-violet-700 hover:underline">&larr; Kembali ke panel admin</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <form method="get" action="{{ route('admin.financial-reports') }}" class="no-print rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"
              x-data="{ preset: @js(old('preset', request('preset', $report->preset))) }">
            <div class="flex flex-wrap items-end gap-4">
                <label class="text-sm">
                    <span class="block font-semibold text-gray-700">Periode</span>
                    <select name="preset" x-model="preset" class="mt-1 rounded-lg border-gray-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                        @foreach ($presets as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm" x-show="preset === 'month'" x-cloak>
                    <span class="block font-semibold text-gray-700">Bulan</span>
                    <input type="month" name="month" value="{{ request('month', $report->start->format('Y-m')) }}" class="mt-1 rounded-lg border-gray-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                </label>
                <label class="text-sm" x-show="preset === 'custom'" x-cloak>
                    <span class="block font-semibold text-gray-700">Tanggal mulai</span>
                    <input type="date" name="start" value="{{ request('start', $report->start->toDateString()) }}" class="mt-1 rounded-lg border-gray-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                </label>
                <label class="text-sm" x-show="preset === 'custom'" x-cloak>
                    <span class="block font-semibold text-gray-700">Tanggal akhir</span>
                    <input type="date" name="end" value="{{ request('end', $report->end->toDateString()) }}" class="mt-1 rounded-lg border-gray-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                </label>
                <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Terapkan</button>
                <div class="ml-auto flex gap-2">
                    <button type="button" onclick="window.print()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cetak Laporan</button>
                    <a href="{{ route('admin.financial-reports.pdf', $report->query()) }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Unduh PDF</a>
                </div>
            </div>
            @if ($errors->any())
                <ul class="mt-3 space-y-1 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </form>

        <section class="print-sheet space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 pb-4">
                <div>
                    <p class="text-2xl font-extrabold">Creative<span class="text-violet-600">Hub</span></p>
                    <p class="text-sm text-gray-500">Laporan Keuangan Marketplace (transaksi simulasi)</p>
                </div>
                <div class="text-right text-sm">
                    <p><span class="text-gray-500">Periode:</span> <strong>{{ $report->periodLabel() }}</strong></p>
                    <p class="text-gray-500">Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    ['Omzet terealisasi', \App\Support\Rupiah::format($summary['revenue']), $summary['success_count'].' transaksi berhasil'],
                    ['Komisi marketplace', \App\Support\Rupiah::format($summary['commission']), 'bagian platform'],
                    ['Pendapatan freelancer', \App\Support\Rupiah::format($summary['freelancer']), 'bagian freelancer'],
                    ['Pending', $summary['pending_count'].' transaksi', \App\Support\Rupiah::format($summary['pending_amount']).' belum terealisasi'],
                ] as [$label, $value, $hint])
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-extrabold">{{ $value }}</p>
                        <p class="text-xs text-gray-500">{{ $hint }}</p>
                    </div>
                @endforeach
            </div>

            <p class="text-sm text-gray-600">
                Jumlah transaksi per status — Berhasil: <strong>{{ $summary['success_count'] }}</strong>,
                Pending: <strong>{{ $summary['pending_count'] }}</strong>,
                Gagal: <strong>{{ $summary['failed_count'] }}</strong>,
                Kedaluwarsa: <strong>{{ $summary['expired_count'] }}</strong>,
                Dibatalkan: <strong>{{ $summary['cancelled_count'] }}</strong>.
                Omzet hanya menghitung transaksi berhasil.
            </p>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="py-2 pr-3">No. Transaksi</th>
                            <th class="py-2 pr-3">Tanggal</th>
                            <th class="py-2 pr-3">Customer</th>
                            <th class="py-2 pr-3">Freelancer</th>
                            <th class="py-2 pr-3">Layanan / Pesanan</th>
                            <th class="py-2 pr-3">Metode</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2 pr-3 text-right">Nilai</th>
                            <th class="py-2 pr-3 text-right">Komisi</th>
                            <th class="py-2 text-right">Freelancer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $payment)
                            @php($isSuccess = $payment->status === \App\Enums\PaymentStatus::Success)
                            <tr>
                                <td class="py-2 pr-3 font-mono text-xs">{{ $payment->transaction_code }}</td>
                                <td class="py-2 pr-3 whitespace-nowrap">{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y H:i') }}</td>
                                <td class="py-2 pr-3">{{ $payment->customer->name }}</td>
                                <td class="py-2 pr-3">{{ $payment->order->freelancer->name }}</td>
                                <td class="py-2 pr-3">{{ $payment->order->title }}</td>
                                <td class="py-2 pr-3">{{ $payment->method->getLabel() }}</td>
                                <td class="py-2 pr-3"><x-status-badge :status="$payment->status" /></td>
                                <td class="py-2 pr-3 text-right">@rupiah($payment->amount)</td>
                                <td class="py-2 pr-3 text-right">{{ $isSuccess ? \App\Support\Rupiah::format($payment->commission_amount) : '-' }}</td>
                                <td class="py-2 text-right">{{ $isSuccess ? \App\Support\Rupiah::format($payment->freelancer_amount) : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="py-8 text-center text-gray-500">Tidak ada transaksi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-gray-300 font-bold">
                        <tr>
                            <td colspan="7" class="py-2 pr-3">Total transaksi berhasil ({{ $summary['success_count'] }})</td>
                            <td class="py-2 pr-3 text-right">@rupiah($summary['revenue'])</td>
                            <td class="py-2 pr-3 text-right">@rupiah($summary['commission'])</td>
                            <td class="py-2 text-right">@rupiah($summary['freelancer'])</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-xs text-gray-500">Seluruh transaksi adalah simulasi untuk demonstrasi tugas kuliah dan tidak memindahkan uang sungguhan. Pembagian komisi hanya pencatatan.</p>
        </section>
    </main>
</body>
</html>

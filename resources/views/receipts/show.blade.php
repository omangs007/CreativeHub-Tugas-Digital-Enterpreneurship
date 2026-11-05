<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $payment->transaction_code }} — CreativeHub</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700,800&display=swap" rel="stylesheet" />
    @include('receipts._styles')
    <style>
        body { margin: 0; background: #e6eaf4; font-family: 'Plus Jakarta Sans', Arial, sans-serif; }
        .toolbar { max-width: 560px; margin: 24px auto 0; display: flex; flex-wrap: wrap; gap: 8px; padding: 0 16px; }
        .toolbar a, .toolbar button { font: inherit; font-size: 14px; font-weight: 600; padding: 8px 14px; border-radius: 8px; border: 1px solid #c9d1e6; background: #fff; color: #10152d; text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: #7c3aed; border-color: #7c3aed; color: #fff; }
        .sheet { max-width: 560px; margin: 16px auto 40px; background: #fff; padding: 28px; box-shadow: 0 10px 30px rgba(16, 21, 45, .12); border-radius: 12px; }
        .receipt { font-family: 'Plus Jakarta Sans', Arial, sans-serif; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; border-radius: 0; }
            @page { size: A5 portrait; margin: 12mm; }
        }
    </style>
</head>
<body>
    <nav class="toolbar" aria-label="Aksi struk">
        <button type="button" class="primary" onclick="window.print()">Cetak Struk</button>
        <a href="{{ route('receipts.pdf', $payment) }}">Unduh PDF</a>
        <a href="{{ route('payments.show', $payment) }}">Kembali</a>
    </nav>

    <main class="sheet">
        @include('receipts._document')
    </main>
</body>
</html>

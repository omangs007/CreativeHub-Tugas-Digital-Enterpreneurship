<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $payment->transaction_code }}</title>
    @include('receipts._styles')
    <style>
        @page { margin: 24px; }
        body { margin: 0; }
    </style>
</head>
<body>
    @include('receipts._document')
</body>
</html>

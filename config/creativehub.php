<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Komisi marketplace
    |--------------------------------------------------------------------------
    |
    | Persentase komisi dalam basis poin (1000 = 10%). Nilai ini di-snapshot ke
    | setiap order saat order dibuat, sehingga perubahan tidak memengaruhi order lama.
    | Pembagian komisi hanya pencatatan simulasi (bukan escrow / payout sungguhan).
    |
    */

    'commission_rate_bps' => (int) env('CREATIVEHUB_COMMISSION_BPS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Upload hasil pekerjaan
    |--------------------------------------------------------------------------
    |
    | Ukuran maksimal file hasil (KB). Default mengikuti upload_max_filesize PHP
    | bawaan (2 MB). Untuk file lebih besar, naikkan upload_max_filesize dan
    | post_max_size di php.ini, atau kirim tautan (misalnya Google Drive).
    |
    */

    'delivery_max_kb' => (int) env('CREATIVEHUB_DELIVERY_MAX_KB', 2048),

    'delivery_extensions' => ['zip', 'rar', '7z', 'pdf', 'png', 'jpg', 'jpeg', 'webp', 'rbxl', 'rbxm'],

];

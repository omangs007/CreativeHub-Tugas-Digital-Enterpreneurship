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

];

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900']) }} role="note">
    <svg class="mt-0.5 size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
    <div>
        <p class="font-bold">{{ __('Mode Demo — pembayaran tidak menggunakan uang sungguhan') }}</p>
        <p class="mt-0.5">{{ __('CreativeHub tidak terhubung ke bank, QRIS, atau e-wallet mana pun. Jangan memasukkan nomor kartu, PIN, OTP, atau kata sandi bank.') }}</p>
    </div>
</div>

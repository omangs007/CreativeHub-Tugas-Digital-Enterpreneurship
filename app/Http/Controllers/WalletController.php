<?php

namespace App\Http\Controllers;

use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Http\Requests\WithdrawalRequest;
use App\Models\Order;
use App\Services\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Saldo freelancer: tertahan (escrow), tersedia, riwayat, dan penarikan dua langkah
 * (pratinjau rincian komisi -> konfirmasi). Token konfirmasi sekali pakai mencegah
 * pengajuan ganda karena klik berulang atau kirim ulang form.
 */
class WalletController extends Controller
{
    private const TOKEN_KEY = 'withdrawal_confirm';

    public function __construct(private readonly Wallet $wallet) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = $this->wallet->walletFor($user);

        $heldOrders = Order::where('freelancer_id', $user->id)
            ->whereHas('walletTransactions', fn ($q) => $q->where('type', WalletTransactionType::EscrowHold))
            ->whereDoesntHave('walletTransactions', fn ($q) => $q->where('type', WalletTransactionType::EscrowRelease))
            ->with('customer')
            ->latest('paid_at')
            ->get();

        $withdrawals = $user->withdrawals();

        return view('freelancer.wallet.index', [
            'wallet' => $wallet,
            'heldOrders' => $heldOrders,
            'withdrawals' => $user->withdrawals()->latest()->limit(20)->get(),
            'transactions' => $wallet->exists
                ? $wallet->transactions()->with(['order', 'withdrawal'])->latest('created_at')->latest('id')->paginate(15)
                : null,
            'totals' => [
                'withdrawn_net' => (int) (clone $withdrawals)->where('status', WithdrawalStatus::Completed)->sum('net_amount'),
                'commission_paid' => (int) (clone $withdrawals)->where('status', WithdrawalStatus::Completed)->sum('commission_amount'),
                'open_amount' => (int) (clone $withdrawals)->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Processing])->sum('amount'),
            ],
            'lastBank' => $user->withdrawals()->latest('id')->first(['bank_name', 'account_number', 'account_holder']),
            'example' => $this->wallet->quote(max($wallet->available_balance, Wallet::minimumWithdrawal())),
        ]);
    }

    /**
     * Langkah 1: tampilkan rincian komisi dan nominal bersih sebelum konfirmasi.
     */
    public function preview(WithdrawalRequest $request): View|RedirectResponse
    {
        $wallet = $this->wallet->walletFor($request->user());
        $amount = (int) $request->validated('amount');

        if ($amount > $wallet->available_balance) {
            throw ValidationException::withMessages(['amount' => __('Nominal melebihi saldo tersedia. Saldo tertahan belum dapat ditarik.')]);
        }

        $token = Str::random(40);
        $request->session()->put(self::TOKEN_KEY, $token);

        return view('freelancer.wallet.confirm', [
            'quote' => $this->wallet->quote($amount),
            'bank' => $request->bank(),
            'wallet' => $wallet,
            'token' => $token,
        ]);
    }

    /**
     * Langkah 2: konfirmasi. Token diambil (dan dihapus) dari sesi sehingga hanya berlaku sekali.
     */
    public function store(WithdrawalRequest $request): RedirectResponse
    {
        $token = $request->session()->pull(self::TOKEN_KEY);

        if (! is_string($token) || ! hash_equals($token, (string) $request->input('confirm_token'))) {
            return redirect()->route('freelancer.wallet')
                ->with('status', __('Konfirmasi penarikan sudah kedaluwarsa atau sudah dikirim. Periksa riwayat penarikan sebelum mengajukan lagi.'));
        }

        $withdrawal = $this->wallet->requestWithdrawal($request->user(), (int) $request->validated('amount'), $request->bank());

        return redirect()->route('freelancer.wallet')
            ->with('status', __('Penarikan :ref diajukan. Status: menunggu diproses admin.', ['ref' => $withdrawal->reference]));
    }
}

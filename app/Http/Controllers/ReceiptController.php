<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Struk pembayaran. Seluruh isi struk diambil dari database (snapshot order dan
 * pembayaran), tidak dari nilai yang dikirim browser.
 */
class ReceiptController extends Controller
{
    public function index(Request $request): View
    {
        return view('receipts.index', [
            'payments' => Payment::where('customer_id', $request->user()->id)
                ->with('order')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(Payment $payment): View
    {
        return view('receipts.show', $this->receiptData($payment));
    }

    public function pdf(Payment $payment): Response
    {
        $data = $this->receiptData($payment);

        return Pdf::loadView('receipts.pdf', $data)
            ->setPaper('a5', 'portrait')
            ->setOption('isFontSubsettingEnabled', true)
            ->download('struk-'.$payment->transaction_code.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptData(Payment $payment): array
    {
        Gate::authorize('view', $payment);
        abort_unless($payment->status === PaymentStatus::Success, 404);

        return [
            'payment' => $payment->load(['order.customer', 'order.freelancer']),
            'order' => $payment->order,
            'printedAt' => now(),
        ];
    }
}

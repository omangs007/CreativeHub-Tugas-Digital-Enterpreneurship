<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGateway;
use App\Services\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Alur pembayaran SIMULASI. Halaman GET hanya menampilkan data; perubahan status
 * hanya terjadi lewat POST yang diproses PaymentProcessor.
 */
class PaymentController extends Controller
{
    public function checkout(Order $order): View
    {
        Gate::authorize('pay', $order);

        return view('payments.checkout', [
            'order' => $order,
            'methods' => PaymentMethod::cases(),
            'pendingPayment' => $order->payments()->where('status', PaymentStatus::Pending)->latest()->first(),
        ]);
    }

    public function start(Request $request, Order $order, PaymentProcessor $processor): RedirectResponse
    {
        Gate::authorize('pay', $order);

        $validated = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
        ]);

        $payment = $processor->start($order, PaymentMethod::from($validated['method']), $request->user());

        return Redirect::route('payments.show', $payment);
    }

    public function show(Payment $payment, PaymentGateway $gateway): View
    {
        Gate::authorize('view', $payment);

        return view('payments.show', [
            'payment' => $payment->load(['order', 'events']),
            'canSimulate' => $gateway->supportsSimulation()
                && $payment->status === PaymentStatus::Pending
                && Gate::allows('simulate', $payment),
        ]);
    }

    public function simulate(Request $request, Payment $payment, PaymentProcessor $processor, PaymentGateway $gateway): RedirectResponse
    {
        Gate::authorize('simulate', $payment);
        abort_unless($gateway->supportsSimulation(), 404);

        $validated = $request->validate([
            'result' => ['required', Rule::in([PaymentStatus::Success->value, PaymentStatus::Pending->value, PaymentStatus::Failed->value])],
        ]);

        $payment = $processor->applyResult($payment, PaymentStatus::from($validated['result']), $request->user());

        return Redirect::route('payments.show', $payment)->with('status', 'payment-'.$payment->status->value);
    }
}

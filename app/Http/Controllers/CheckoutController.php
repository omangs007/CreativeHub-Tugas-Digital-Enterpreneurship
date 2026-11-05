<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServicePackage;
use App\Services\OrderPlacement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Checkout paket layanan: konfirmasi lalu pembuatan order. Seluruh data harga
 * diambil dari database; field harga apa pun yang dikirim browser diabaikan.
 */
class CheckoutController extends Controller
{
    public function create(Request $request, ServicePackage $package): View
    {
        $service = Service::publiclyVisible()->with(['category', 'freelancer'])->find($package->service_id);

        abort_unless($service && $package->is_active, 404);

        return view('checkout.package', [
            'service' => $service,
            'package' => $package,
            'isOwner' => $service->user_id === $request->user()->id,
        ]);
    }

    public function store(Request $request, ServicePackage $package, OrderPlacement $placement): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'agree' => ['accepted'],
        ], [
            'agree.accepted' => __('Centang persetujuan sebelum membuat pesanan.'),
        ]);

        $order = $placement->orderPackage($package, $request->user(), $validated['note'] ?? null);

        return Redirect::route('orders.show', $order)->with('status', 'order-created');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDelivery;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderWorkflowController extends Controller
{
    public function deliver(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('deliver', $order);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'link_url' => ['nullable', 'url:http,https', 'max:500', 'required_without:file'],
            'file' => [
                'nullable',
                'file',
                'max:'.config('creativehub.delivery_max_kb'),
                'extensions:'.implode(',', config('creativehub.delivery_extensions')),
                'required_without:link_url',
            ],
        ], [
            'link_url.required_without' => __('Lampirkan file hasil atau isi tautan hasil.'),
            'file.required_without' => __('Lampirkan file hasil atau isi tautan hasil.'),
        ], [
            'message' => 'catatan pengiriman',
            'link_url' => 'tautan hasil',
            'file' => 'file hasil',
        ]);

        $workflow->deliver($order, $request->user(), $validated['message'], $validated['link_url'] ?? null, $request->file('file'));

        return Redirect::route('orders.show', $order)->with('status', 'order-delivered');
    }

    public function requestRevision(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('review', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ], [], ['reason' => 'detail revisi']);

        $workflow->requestRevision($order, $request->user(), $validated['reason']);

        return Redirect::route('orders.show', $order)->with('status', 'revision-requested');
    }

    public function complete(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('review', $order);

        $workflow->complete($order, $request->user());

        return Redirect::route('orders.show', $order)->with('status', 'order-completed');
    }

    public function cancel(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('pay', $order);

        $workflow->cancelUnpaid($order, $request->user());

        return Redirect::route('orders.show', $order)->with('status', 'order-cancelled');
    }

    /**
     * File hasil hanya bisa diunduh pihak yang terlibat dalam pesanan atau admin.
     */
    public function download(OrderDelivery $delivery): StreamedResponse
    {
        Gate::authorize('view', $delivery->order);
        abort_unless($delivery->hasFile() && Storage::disk(OrderDelivery::DISK)->exists($delivery->file_path), 404);

        return Storage::disk(OrderDelivery::DISK)->download($delivery->file_path, $delivery->file_name);
    }
}

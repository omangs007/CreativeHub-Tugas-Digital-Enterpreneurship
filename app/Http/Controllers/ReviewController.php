<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Notifications\ActivityNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('reviewOrder', $order);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], ['rating' => 'rating', 'comment' => 'komentar']);

        $review = new Review;
        $review->order_id = $order->id;
        $review->service_id = $order->service_id;
        $review->customer_id = $order->customer_id;
        $review->freelancer_id = $order->freelancer_id;
        $review->rating = (int) $validated['rating'];
        $review->comment = $validated['comment'] ?? null;

        try {
            $review->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['rating' => __('Pesanan ini sudah diulas.')]);
        }

        ActivityNotification::send($order->freelancer, __('Ulasan baru'),
            __(':name memberi rating :rating untuk :title.', ['name' => $order->customer->name, 'rating' => $review->rating.'/5', 'title' => $order->title]),
            'orders.show', $order);

        return Redirect::route('orders.show', $order)->with('status', 'review-created');
    }
}

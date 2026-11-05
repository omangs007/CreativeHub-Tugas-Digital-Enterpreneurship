<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PackageTier;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderPlacement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function completedPackageOrder(): Order
    {
        $service = Service::factory()->published()->withPackages()->create(['title' => 'Desain Poster Konser']);
        $package = $service->packages()->where('tier', PackageTier::Basic)->sole();
        $order = app(OrderPlacement::class)->orderPackage($package, User::factory()->customer()->create());
        $order->forceFill(['status' => OrderStatus::Completed, 'paid_at' => now(), 'completed_at' => now()])->save();

        return $order;
    }

    public function test_customer_can_review_completed_order_once(): void
    {
        $order = $this->completedPackageOrder();

        $this->actingAs($order->customer)->get("/dashboard/orders/{$order->id}")->assertSee('Beri ulasan');

        $this->actingAs($order->customer)
            ->post("/dashboard/orders/{$order->id}/review", ['rating' => 4, 'comment' => 'Hasil bagus dan cepat.'])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/dashboard/orders/{$order->id}");

        $review = Review::sole();
        $this->assertSame(4, $review->rating);
        $this->assertSame($order->service_id, $review->service_id);
        $this->assertSame($order->freelancer_id, $review->freelancer_id);

        $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/review", ['rating' => 5])->assertForbidden();
        $this->assertSame(1, Review::count());
    }

    public function test_review_requires_completed_order_and_owner(): void
    {
        foreach ([OrderStatus::PendingPayment, OrderStatus::InProgress, OrderStatus::Delivered] as $status) {
            $order = Order::factory()->status($status)->create();
            $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/review", ['rating' => 5])->assertForbidden();
        }

        $order = $this->completedPackageOrder();
        $this->actingAs($order->freelancer)->post("/dashboard/orders/{$order->id}/review", ['rating' => 5])->assertForbidden();
        $this->actingAs(User::factory()->customer()->create())->post("/dashboard/orders/{$order->id}/review", ['rating' => 5])->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rating_is_validated(): void
    {
        $order = $this->completedPackageOrder();

        foreach ([0, 6, 'lima'] as $rating) {
            $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/review", ['rating' => $rating])->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_database_enforces_rating_range_and_one_review_per_order(): void
    {
        $order = $this->completedPackageOrder();
        $review = new Review;
        $review->order_id = $order->id;
        $review->customer_id = $order->customer_id;
        $review->freelancer_id = $order->freelancer_id;
        $review->rating = 9;

        $this->expectException(QueryException::class);
        $review->save();
    }

    public function test_visible_reviews_appear_on_service_card_detail_and_profile(): void
    {
        $order = $this->completedPackageOrder();
        $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/review", ['rating' => 5, 'comment' => 'Mantap sekali hasilnya.']);

        $this->get('/services')->assertSee('5,0 (1)');
        $this->get("/services/{$order->service->slug}")->assertSee('Mantap sekali hasilnya.')->assertSee('5,0 (1)');
        $this->get("/freelancers/{$order->freelancer_id}")->assertSee('Mantap sekali hasilnya.');
    }

    public function test_hidden_reviews_are_not_shown_publicly(): void
    {
        $order = $this->completedPackageOrder();
        $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/review", ['rating' => 1, 'comment' => 'Ulasan yang disembunyikan admin.']);
        Review::sole()->forceFill(['is_visible' => false])->save();

        $this->get("/services/{$order->service->slug}")->assertDontSee('Ulasan yang disembunyikan admin.')->assertSee('Belum ada ulasan');
        $this->get("/freelancers/{$order->freelancer_id}")->assertDontSee('Ulasan yang disembunyikan admin.');
        $this->get('/services')->assertDontSee('1,0 (1)');
    }
}

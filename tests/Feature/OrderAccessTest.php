<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\RoleName;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_can_view_order_and_others_cannot(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->customer)->get("/dashboard/orders/{$order->id}")->assertOk()->assertSee($order->order_number);
        $this->actingAs($order->freelancer)->get("/dashboard/orders/{$order->id}")->assertOk()->assertSee('Status dana');
        $this->actingAs(User::factory()->admin()->create())->get("/dashboard/orders/{$order->id}")->assertOk();

        $this->actingAs(User::factory()->customer()->create())->get("/dashboard/orders/{$order->id}")->assertForbidden();
        $this->actingAs(User::factory()->freelancer()->create())->get("/dashboard/orders/{$order->id}")->assertForbidden();
        $this->get('/logout');
    }

    public function test_guest_is_redirected_from_orders(): void
    {
        $order = Order::factory()->create();

        $this->get('/dashboard/orders')->assertRedirect(route('login'));
        $this->get("/dashboard/orders/{$order->id}")->assertRedirect(route('login'));
    }

    public function test_order_list_shows_customer_and_freelancer_orders_separately(): void
    {
        $asCustomer = Order::factory()->create(['title' => 'Pesanan Saya Sebagai Customer']);
        $user = $asCustomer->customer;
        $user->assignRole(RoleName::Freelancer);
        $asFreelancer = Order::factory()->create(['freelancer_id' => $user->id, 'title' => 'Pesanan Masuk Untuk Saya']);
        Order::factory()->create(['title' => 'Pesanan Orang Lain']);

        $this->actingAs($user)->get('/dashboard/orders')
            ->assertOk()
            ->assertSee('Pesanan Saya Sebagai Customer')
            ->assertDontSee('Pesanan Masuk Untuk Saya')
            ->assertDontSee('Pesanan Orang Lain');

        $this->actingAs($user)->get('/dashboard/orders?sebagai=freelancer')
            ->assertOk()
            ->assertSee('Pesanan Masuk Untuk Saya')
            ->assertDontSee('Pesanan Saya Sebagai Customer');
    }

    public function test_order_status_transitions_are_enforced_and_recorded(): void
    {
        $order = Order::factory()->create();

        $order->transitionTo(OrderStatus::Paid, $order->customer, 'Pembayaran simulasi berhasil');
        $order->transitionTo(OrderStatus::InProgress);

        $this->assertSame(OrderStatus::InProgress, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertSame(2, $order->statusHistories()->count());

        $this->expectException(ValidationException::class);
        $order->transitionTo(OrderStatus::Completed);
    }

    public function test_user_with_orders_cannot_delete_account(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->customer)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertModelExists($order->customer);
        $this->assertAuthenticated();
    }

    public function test_database_rejects_inconsistent_amounts(): void
    {
        $this->expectException(QueryException::class);

        Order::factory()->create(['total_amount' => 500_000, 'subtotal_amount' => 500_000, 'commission_amount' => 50_000, 'freelancer_amount' => 400_000]);
    }

    public function test_database_rejects_order_without_source(): void
    {
        $this->expectException(QueryException::class);

        Order::factory()->create([
            'proposal_id' => null,
            'project_id' => null,
            'customer_id' => User::factory()->customer(),
            'freelancer_id' => User::factory()->freelancer(),
            'title' => 'Order tanpa sumber',
        ]);
    }
}

<?php

namespace Tests\Feature\Customer;

use App\Enums\OrderStatus;
use App\Enums\PackageTier;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPackageTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private ServicePackage $standard;

    protected function setUp(): void
    {
        parent::setUp();

        config(['creativehub.commission_rate_bps' => 1000]);
        $this->service = Service::factory()->published()->withPackages(150_000)->create(['title' => 'Desain Poster Event']);
        $this->standard = $this->service->packages()->where('tier', PackageTier::Standard)->sole();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get("/checkout/package/{$this->standard->id}")->assertRedirect(route('login'));
        $this->post("/checkout/package/{$this->standard->id}", ['agree' => '1'])->assertRedirect(route('login'));
    }

    public function test_service_page_links_to_checkout(): void
    {
        $this->get("/services/{$this->service->slug}")->assertSee(route('checkout.package', $this->standard));
    }

    public function test_customer_sees_confirmation_from_database(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get("/checkout/package/{$this->standard->id}")
            ->assertOk()
            ->assertSee('Desain Poster Event')
            ->assertSee('Rp300.000')
            ->assertSee('Buat Pesanan');
    }

    public function test_customer_can_order_package_and_browser_price_is_ignored(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->post("/checkout/package/{$this->standard->id}", [
            'agree' => '1',
            'note' => 'Tema warna ungu dan biru.',
            'price' => 1,
            'total_amount' => 1,
            'freelancer_id' => $customer->id,
        ]);

        $order = Order::sole();
        $response->assertSessionHasNoErrors()->assertRedirect("/dashboard/orders/{$order->id}");

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame($this->service->user_id, $order->freelancer_id);
        $this->assertSame($this->service->id, $order->service_id);
        $this->assertSame($this->standard->id, $order->service_package_id);
        $this->assertNull($order->proposal_id);
        $this->assertSame('Desain Poster Event', $order->title);
        $this->assertSame('standard', $order->package_tier);
        $this->assertSame(300_000, $order->total_amount);
        $this->assertSame(30_000, $order->commission_amount);
        $this->assertSame(270_000, $order->freelancer_amount);
        $this->assertSame($this->standard->delivery_days, $order->delivery_days);
        $this->assertSame($this->standard->revisions, $order->revisions_allowed);
        $this->assertStringContainsString('Tema warna ungu dan biru.', $order->scope_description);
        $this->assertSame(1, $order->statusHistories()->count());
    }

    public function test_snapshot_does_not_change_when_package_is_edited(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post("/checkout/package/{$this->standard->id}", ['agree' => '1']);

        $this->standard->update(['price' => 999_000, 'description' => 'Isi paket baru', 'delivery_days' => 30]);
        $this->service->update(['title' => 'Judul Layanan Baru']);

        $order = Order::sole();
        $this->assertSame(300_000, $order->total_amount);
        $this->assertSame('Desain Poster Event', $order->title);
        $this->assertNotSame(30, $order->delivery_days);
        $this->assertStringNotContainsString('Isi paket baru', $order->scope_description);
    }

    public function test_double_submit_reuses_pending_order(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post("/checkout/package/{$this->standard->id}", ['agree' => '1']);
        $this->actingAs($customer)->post("/checkout/package/{$this->standard->id}", ['agree' => '1']);

        $this->assertSame(1, Order::count());
    }

    public function test_agreement_is_required(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post("/checkout/package/{$this->standard->id}", [])->assertSessionHasErrors('agree');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_freelancer_cannot_buy_own_service(): void
    {
        $owner = $this->service->freelancer;

        $this->actingAs($owner)->get("/checkout/package/{$this->standard->id}")->assertOk()->assertDontSee('Buat Pesanan');
        $this->actingAs($owner)->post("/checkout/package/{$this->standard->id}", ['agree' => '1'])->assertSessionHasErrors('package');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unavailable_packages_cannot_be_ordered(): void
    {
        $customer = User::factory()->customer()->create();

        $draft = Service::factory()->status(ServiceStatus::Draft)->withPackages()->create();
        $inactiveCategory = Service::factory()->published()->withPackages()->for(Category::factory()->inactive())->create();
        $inactivePackage = $this->service->packages()->where('tier', PackageTier::Premium)->sole();
        $inactivePackage->update(['is_active' => false]);

        foreach ([$draft->packages()->first(), $inactiveCategory->packages()->first(), $inactivePackage] as $package) {
            $this->actingAs($customer)->get("/checkout/package/{$package->id}")->assertNotFound();
            $this->actingAs($customer)->post("/checkout/package/{$package->id}", ['agree' => '1'])->assertSessionHasErrors('package');
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_only_account_cannot_order(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/checkout/package/{$this->standard->id}", ['agree' => '1'])->assertSessionHasErrors('package');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_ordered_service_cannot_be_deleted_by_freelancer(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post("/checkout/package/{$this->standard->id}", ['agree' => '1']);

        $owner = $this->service->freelancer;
        $this->actingAs($owner)->post("/dashboard/services/{$this->service->id}/withdraw")->assertSessionHasNoErrors();
        $this->actingAs($owner)->delete("/dashboard/services/{$this->service->id}")->assertSessionHasErrors('service');

        $this->assertModelExists($this->service);
    }
}

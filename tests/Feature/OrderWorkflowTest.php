<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProjectStatus;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['creativehub.delivery_max_kb' => 2048]);

        $this->order = Order::factory()->status(OrderStatus::InProgress)->create(['revisions_allowed' => 1]);
    }

    private function deliver(array $payload = []): TestResponse
    {
        return $this->actingAs($this->order->freelancer)->post("/dashboard/orders/{$this->order->id}/deliveries", [
            'message' => 'Berikut hasil map dan file pendukung.',
            'file' => UploadedFile::fake()->create('Hasil Map <final>.zip', 500, 'application/zip'),
            ...$payload,
        ]);
    }

    public function test_assigned_freelancer_can_deliver_with_private_file(): void
    {
        $this->deliver()->assertSessionHasNoErrors()->assertRedirect("/dashboard/orders/{$this->order->id}");

        $delivery = OrderDelivery::sole();
        $this->assertSame(OrderStatus::Delivered, $this->order->fresh()->status);
        $this->assertNotNull($this->order->fresh()->delivered_at);
        $this->assertStringStartsWith("deliveries/{$this->order->id}/", $delivery->file_path);
        $this->assertSame('hasil-map-final.zip', $delivery->file_name);
        Storage::disk('local')->assertExists($delivery->file_path);
        // Disimpan di disk privat, bukan disk publik.
        $this->assertStringNotContainsString('public', $delivery->file_path);
    }

    public function test_delivery_can_use_link_only(): void
    {
        $this->deliver(['file' => null, 'link_url' => 'https://drive.google.com/file/d/contoh'])->assertSessionHasNoErrors();

        $this->assertSame('https://drive.google.com/file/d/contoh', OrderDelivery::sole()->link_url);
    }

    public function test_delivery_requires_file_or_link_and_validates_file(): void
    {
        $this->deliver(['file' => null])->assertSessionHasErrors(['file', 'link_url']);
        $this->deliver(['file' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('file');
        $this->deliver(['file' => UploadedFile::fake()->create('script.php', 10)])->assertSessionHasErrors('file');
        $this->deliver(['file' => UploadedFile::fake()->create('besar.zip', 3000)])->assertSessionHasErrors('file');
        $this->deliver(['file' => null, 'link_url' => 'javascript:alert(1)'])->assertSessionHasErrors('link_url');

        $this->assertDatabaseCount('order_deliveries', 0);
        $this->assertSame(OrderStatus::InProgress, $this->order->fresh()->status);
    }

    public function test_only_assigned_freelancer_can_deliver_and_only_when_working(): void
    {
        $this->actingAs($this->order->customer)
            ->post("/dashboard/orders/{$this->order->id}/deliveries", ['message' => 'Saya customer mencoba kirim', 'link_url' => 'https://contoh.test'])
            ->assertForbidden();
        $this->actingAs(User::factory()->freelancer()->create())
            ->post("/dashboard/orders/{$this->order->id}/deliveries", ['message' => 'Freelancer lain mencoba', 'link_url' => 'https://contoh.test'])
            ->assertForbidden();

        $unpaid = Order::factory()->create();
        $this->actingAs($unpaid->freelancer)
            ->post("/dashboard/orders/{$unpaid->id}/deliveries", ['message' => 'Belum dibayar tapi kirim', 'link_url' => 'https://contoh.test'])
            ->assertForbidden();

        $this->assertDatabaseCount('order_deliveries', 0);
    }

    public function test_customer_can_request_revision_within_allowance_then_freelancer_redelivers(): void
    {
        $this->deliver();

        $this->actingAs($this->order->customer)
            ->post("/dashboard/orders/{$this->order->id}/revisions", ['reason' => 'Tolong ubah warna lantai menjadi biru.'])
            ->assertSessionHasNoErrors();

        $order = $this->order->fresh();
        $this->assertSame(OrderStatus::RevisionRequested, $order->status);
        $this->assertSame(1, $order->revisions_used);
        $this->assertSame(OrderDelivery::sole()->id, $order->revisions()->sole()->order_delivery_id);

        $this->deliver(['message' => 'Revisi warna lantai sudah selesai.'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Delivered, $this->order->fresh()->status);

        // Jatah revisi (1) sudah habis.
        $this->actingAs($this->order->customer)
            ->post("/dashboard/orders/{$this->order->id}/revisions", ['reason' => 'Revisi tambahan lagi ya.'])
            ->assertSessionHasErrors('reason');
        $this->assertSame(OrderStatus::Delivered, $this->order->fresh()->status);
    }

    public function test_customer_can_complete_delivered_order(): void
    {
        $this->deliver();

        $this->actingAs($this->order->customer)->post("/dashboard/orders/{$this->order->id}/complete")->assertSessionHasNoErrors();

        $order = $this->order->fresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertNotNull($order->completed_at);
        // Order dari proposal: proyek ikut selesai.
        $this->assertSame(ProjectStatus::Completed, $order->project->status);
    }

    public function test_only_customer_can_review_and_only_after_delivery(): void
    {
        $this->actingAs($this->order->customer)->post("/dashboard/orders/{$this->order->id}/complete")->assertForbidden();

        $this->deliver();

        $this->actingAs($this->order->freelancer)->post("/dashboard/orders/{$this->order->id}/complete")->assertForbidden();
        $this->actingAs($this->order->freelancer)
            ->post("/dashboard/orders/{$this->order->id}/revisions", ['reason' => 'Freelancer minta revisi sendiri'])
            ->assertForbidden();
        $this->actingAs(User::factory()->customer()->create())->post("/dashboard/orders/{$this->order->id}/complete")->assertForbidden();

        $this->assertSame(OrderStatus::Delivered, $this->order->fresh()->status);
    }

    public function test_completed_order_cannot_receive_more_actions(): void
    {
        $this->deliver();
        $this->actingAs($this->order->customer)->post("/dashboard/orders/{$this->order->id}/complete");

        $this->actingAs($this->order->customer)->post("/dashboard/orders/{$this->order->id}/complete")->assertForbidden();
        $this->deliver()->assertForbidden();
    }

    public function test_delivery_file_download_is_restricted_to_participants(): void
    {
        $this->deliver();
        $delivery = OrderDelivery::sole();

        $this->actingAs($this->order->customer)->get("/dashboard/deliveries/{$delivery->id}/download")
            ->assertOk()
            ->assertDownload('hasil-map-final.zip');
        $this->actingAs($this->order->freelancer)->get("/dashboard/deliveries/{$delivery->id}/download")->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get("/dashboard/deliveries/{$delivery->id}/download")->assertOk();

        $this->actingAs(User::factory()->customer()->create())->get("/dashboard/deliveries/{$delivery->id}/download")->assertForbidden();
    }

    public function test_order_page_shows_forms_by_role(): void
    {
        $this->actingAs($this->order->freelancer)->get("/dashboard/orders/{$this->order->id}")->assertSee('Kirim Hasil');
        $this->actingAs($this->order->customer)->get("/dashboard/orders/{$this->order->id}")->assertDontSee('Kirim Hasil');

        $this->deliver();

        $this->actingAs($this->order->customer)->get("/dashboard/orders/{$this->order->id}")
            ->assertSee('Terima Hasil &amp; Selesaikan', false)
            ->assertSee('Minta Revisi')
            ->assertSee('hasil-map-final.zip');
    }
}

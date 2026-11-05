<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_participants_can_start_and_reuse_conversation(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->customer)->get("/dashboard/orders/{$order->id}/chat")->assertRedirect();
        $this->actingAs($order->freelancer)->get("/dashboard/orders/{$order->id}/chat")->assertRedirect();

        $conversation = Conversation::sole();
        $this->assertSame($order->id, $conversation->order_id);
        $this->assertSame($order->customer_id, $conversation->customer_id);
        $this->assertSame($order->freelancer_id, $conversation->freelancer_id);
    }

    public function test_outsiders_and_admin_cannot_open_order_chat(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->customer()->create())->get("/dashboard/orders/{$order->id}/chat")->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get("/dashboard/orders/{$order->id}/chat")->assertForbidden();
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_participants_can_send_and_read_messages(): void
    {
        $order = Order::factory()->create();
        $conversation = Conversation::forOrder($order);

        $this->actingAs($order->customer)
            ->post("/dashboard/messages/{$conversation->id}", ['body' => 'Halo, kapan bisa mulai?'])
            ->assertRedirect("/dashboard/messages/{$conversation->id}");

        $this->actingAs($order->freelancer)
            ->postJson("/dashboard/messages/{$conversation->id}", ['body' => 'Besok pagi saya mulai.'])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Besok pagi saya mulai.')
            ->assertJsonPath('message.mine', true);

        $this->assertSame(2, Message::count());
        $this->assertNotNull($conversation->fresh()->last_message_at);

        $this->actingAs($order->customer)
            ->get("/dashboard/messages/{$conversation->id}")
            ->assertOk()
            ->assertSee('Halo, kapan bisa mulai?')
            ->assertSee('Besok pagi saya mulai.');

        // Pesan dari freelancer ditandai terbaca saat customer membuka percakapan.
        $this->assertNotNull(Message::where('sender_id', $order->freelancer_id)->sole()->read_at);
    }

    public function test_poll_returns_only_new_messages_as_json(): void
    {
        $order = Order::factory()->create();
        $conversation = Conversation::forOrder($order);
        $this->actingAs($order->customer)->postJson("/dashboard/messages/{$conversation->id}", ['body' => 'Pesan pertama']);
        $first = Message::sole();
        $this->actingAs($order->freelancer)->postJson("/dashboard/messages/{$conversation->id}", ['body' => '<script>alert(1)</script>']);

        $this->actingAs($order->customer)
            ->getJson("/dashboard/messages/{$conversation->id}/poll?after={$first->id}")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', '<script>alert(1)</script>')
            ->assertJsonPath('messages.0.mine', false);
    }

    public function test_outsiders_cannot_read_poll_or_send(): void
    {
        $order = Order::factory()->create();
        $conversation = Conversation::forOrder($order);
        $outsider = User::factory()->freelancer()->create();

        $this->actingAs($outsider)->get("/dashboard/messages/{$conversation->id}")->assertForbidden();
        $this->actingAs($outsider)->getJson("/dashboard/messages/{$conversation->id}/poll")->assertForbidden();
        $this->actingAs($outsider)->postJson("/dashboard/messages/{$conversation->id}", ['body' => 'Menyusup'])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get("/dashboard/messages/{$conversation->id}")->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_message_is_validated(): void
    {
        $order = Order::factory()->create();
        $conversation = Conversation::forOrder($order);

        $this->actingAs($order->customer)->postJson("/dashboard/messages/{$conversation->id}", ['body' => ''])->assertUnprocessable();
        $this->actingAs($order->customer)->postJson("/dashboard/messages/{$conversation->id}", ['body' => str_repeat('a', 2001)])->assertUnprocessable();
    }

    public function test_proposal_chat_is_limited_to_project_owner_and_proposer(): void
    {
        $proposal = Proposal::factory()->create();

        $this->actingAs($proposal->freelancer)->get("/dashboard/proposals/{$proposal->id}/chat")->assertRedirect();
        $this->actingAs($proposal->project->customer)->get("/dashboard/proposals/{$proposal->id}/chat")->assertRedirect();
        $this->actingAs(User::factory()->freelancer()->create())->get("/dashboard/proposals/{$proposal->id}/chat")->assertForbidden();

        $conversation = Conversation::sole();
        $this->assertSame($proposal->id, $conversation->proposal_id);
        $this->assertNull($conversation->order_id);
    }

    public function test_inbox_lists_only_own_conversations_with_unread_count(): void
    {
        $order = Order::factory()->create(['title' => 'Pesanan Dengan Chat']);
        $conversation = Conversation::forOrder($order);
        $this->actingAs($order->freelancer)->postJson("/dashboard/messages/{$conversation->id}", ['body' => 'Ada pertanyaan?']);
        $other = Order::factory()->create(['title' => 'Chat Orang Lain']);
        Conversation::forOrder($other);

        $this->actingAs($order->customer)->get('/dashboard/messages')
            ->assertOk()
            ->assertSee('Pesanan Dengan Chat')
            ->assertSee('Ada pertanyaan?')
            ->assertDontSee('Chat Orang Lain');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Proposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Chat sederhana berbasis polling HTTP (tanpa layanan real-time eksternal).
 */
class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::forParticipant($user)
            ->with(['customer', 'freelancer', 'order', 'proposal.project', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->where('sender_id', '!=', $user->id)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('messages.index', ['conversations' => $conversations]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $user = $request->user();
        $this->markRead($conversation, $user->id);

        $messages = $conversation->messages()->with('sender')->latest('id')->limit(100)->get()->reverse()->values();

        return view('messages.show', [
            'conversation' => $conversation->load(['customer', 'freelancer', 'order', 'proposal.project']),
            'other' => $conversation->otherParticipant($user),
            'messages' => $messages->map(fn (Message $m) => $m->toChatArray($user)),
        ]);
    }

    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $after = max(0, (int) $request->query('after', 0));
        $user = $request->user();

        $messages = $conversation->messages()->with('sender')->where('id', '>', $after)->orderBy('id')->limit(50)->get();
        $this->markRead($conversation, $user->id);

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => $m->toChatArray($user))->values(),
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        Gate::authorize('send', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => 'pesan']);

        $body = trim($validated['body']);

        abort_if($body === '', 422);

        $message = DB::transaction(function () use ($conversation, $request, $body) {
            $message = new Message;
            $message->conversation_id = $conversation->id;
            $message->sender_id = $request->user()->id;
            $message->body = $body;
            $message->save();

            $conversation->last_message_at = $message->created_at;
            $conversation->save();

            return $message;
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => $message->load('sender')->toChatArray($request->user())], 201);
        }

        return Redirect::route('messages.show', $conversation);
    }

    /**
     * Hanya customer dan freelancer pesanan yang dapat membuka chat (admin tidak membaca chat privat).
     */
    public function startForOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->involves($request->user()), 403);

        return Redirect::route('messages.show', Conversation::forOrder($order));
    }

    public function startForProposal(Request $request, Proposal $proposal): RedirectResponse
    {
        $user = $request->user();
        abort_unless($proposal->freelancer_id === $user->id || $proposal->project->customer_id === $user->id, 403);

        return Redirect::route('messages.show', Conversation::forProposal($proposal));
    }

    private function markRead(Conversation $conversation, int $userId): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->update(['read_at' => now()]);
    }
}

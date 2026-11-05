<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $guarded = ['*'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Bentuk data untuk polling JSON. Isi pesan dirender sebagai teks di browser.
     *
     * @return array<string, mixed>
     */
    public function toChatArray(User $viewer): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'mine' => $this->sender_id === $viewer->id,
            'sender' => $this->sender->name,
            'time' => $this->created_at->translatedFormat('d M H:i'),
        ];
    }
}

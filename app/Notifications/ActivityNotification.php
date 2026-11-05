<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi aktivitas di dashboard (kanal database). Tautan selalu path internal aplikasi.
 */
class ActivityNotification extends Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $path,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'path' => $this->path,
        ];
    }

    /**
     * Kirim notifikasi dengan tautan ke rute bernama (disimpan sebagai path relatif).
     *
     * @param  array<string, mixed>|mixed  $parameters
     */
    public static function send(?object $user, string $title, string $body, string $route, mixed $parameters = []): void
    {
        $user?->notify(new self($title, $body, route($route, $parameters, absolute: false)));
    }
}

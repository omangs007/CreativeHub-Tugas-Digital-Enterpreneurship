<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(string $action, ?Model $subject = null, ?array $old = null, ?array $new = null, ?User $actor = null): AuditLog
    {
        $log = new AuditLog;
        $log->user_id = ($actor ?? auth()->user())?->getKey();
        $log->action = $action;
        $log->auditable()->associate($subject);
        $log->old_values = $old;
        $log->new_values = $new;
        $log->ip_address = request()?->ip();
        $log->save();

        return $log;
    }
}

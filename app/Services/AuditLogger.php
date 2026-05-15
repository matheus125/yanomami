<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): void
    {
        try {
            $request = request();
            $user = $request?->user();

            ActivityLog::create([
                'user_id' => $user?->id,
                'table_name' => $subject?->getTable(),
                'action' => $action,
                'record_id' => $subject?->getKey(),
                'subject_type' => $subject ? $subject::class : null,
                'description' => $description,
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Falha ao registrar log de auditoria.', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

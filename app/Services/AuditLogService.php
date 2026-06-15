<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Log an audit event.
     */
    public function log(
        string $event,
        string $module,
        mixed $auditable,
        string $description,
        ?array $before = null,
        ?array $after = null
    ): void {
        Log::info("Audit Log [{$module}.{$event}]: {$description}", [
            'auditable_type' => is_object($auditable) ? get_class($auditable) : null,
            'auditable_id' => is_object($auditable) && isset($auditable->id) ? $auditable->id : null,
            'before' => $before,
            'after' => $after,
        ]);
    }

    /**
     * Map roles to names.
     */
    public function roleNames(mixed $roles): array
    {
        if (is_array($roles)) {
            return $roles;
        }

        return [];
    }

    /**
     * Map permissions to names.
     */
    public function permissionNames(mixed $permissions): array
    {
        if (is_array($permissions)) {
            return $permissions;
        }

        return [];
    }
}

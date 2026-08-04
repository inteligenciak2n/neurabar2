<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Registra ações sensíveis do backoffice.
 *
 * A auditoria nunca pode derrubar a operação que ela observa: qualquer falha
 * de escrita vira log de aplicação em vez de exceção.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(string $action, ?Model $auditable = null, ?array $before = null, ?array $after = null): ?AuditLog
    {
        try {
            /** @var User|null $actor */
            $actor = Auth::user();

            return AuditLog::create([
                'action' => $action,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'actor_id' => $actor?->getKey(),
                'actor_name' => $actor?->name,
                'before' => $before,
                'after' => $after,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255) ?: null,
            ]);
        } catch (Throwable $exception) {
            Log::error('audit.log.failed', [
                'action' => $action,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Snapshot apenas dos atributos que interessam à auditoria.
     *
     * @param  list<string>  $attributes
     * @return array<string, mixed>
     */
    public static function snapshot(Model $model, array $attributes): array
    {
        $snapshot = [];

        foreach ($attributes as $attribute) {
            $snapshot[$attribute] = $model->getAttribute($attribute);
        }

        return $snapshot;
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\ModuleCode;
use App\Services\Billing\BillingStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireModule
{
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        $venue = app('tenant');
        $module = ModuleCode::tryFrom($moduleCode);

        if (! $venue || ! $module) {
            abort(403, 'Módulo não disponível.');
        }

        if (BillingStatusService::isBlocked($venue)) {
            abort(403, 'Acesso suspenso por questões de faturamento.');
        }

        $activeModules = $venue->activeModules();

        if (! in_array($module->value, $activeModules, true)) {
            abort(403, 'Este módulo não está contratado para esta conta.');
        }

        foreach ($module->dependsOn() as $dependency) {
            if (! in_array($dependency->value, $activeModules, true)) {
                abort(403, "Dependência não atendida: {$dependency->label()}.");
            }
        }

        return $next($request);
    }
}

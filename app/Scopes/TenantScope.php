<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Não aplica filtro quando:
     * - Não há tenant no container (contexto de console/queue sem venue)
     * - O tenant é dedicado (banco exclusivo — todas as linhas já pertencem ao tenant)
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('tenant')) {
            return;
        }

        $tenant = app('tenant');

        if ($tenant === null) {
            return;
        }

        // Bancos dedicados não precisam de filtro por venue_id —
        // o banco inteiro pertence à corporation do tenant.
        if (app()->bound('operational_is_dedicated') && app('operational_is_dedicated') === true) {
            return;
        }

        $builder->where($model->getTable().'.venue_id', $tenant->id);
    }
}

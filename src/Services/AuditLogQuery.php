<?php

namespace CbyteDigital\Nen7513AuditLogging\Services;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditAction;
use CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AuditLogQuery - Query builder for audit logs
 * 
 * Provides a fluent interface for querying audit logs
 * with various filters and search capabilities.
 */
class AuditLogQuery
{
    private Builder $query;
    
    public function __construct()
    {
        $this->query = DB::table(config('audit-log.table_name', 'audit_logs'));
    }

    /**
     * Filter op een specifieke actor
     */
    public function forActor(Model $actor): self
    {
        $this->query->where('actor_type', get_class($actor))
                   ->where('actor_id', $actor->getKey());
        
        return $this;
    }

    /**
     * Filter op actor type (class name)
     */
    public function forActorType(string $actorType): self
    {
        $this->query->where('actor_type', $actorType);
        return $this;
    }

    /**
     * Filter op actor rol
     */
    public function forActorRole(string $role): self
    {
        $this->query->where('actor_role', $role);
        return $this;
    }

    /**
     * Filter op een specifiek object
     */
    public function forObject(string $objectType, string $objectId): self
    {
        $this->query->where('object_type', $objectType)
                   ->where('object_id', $objectId);
        
        return $this;
    }

    /**
     * Filter op object type
     */
    public function forObjectType(string $objectType): self
    {
        $this->query->where('object_type', $objectType);
        return $this;
    }

    /**
     * Filter op een specifieke actie
     */
    public function withAction(AuditAction $action): self
    {
        $this->query->where('action', $action->value);
        return $this;
    }

    /**
     * Filter op meerdere acties
     */
    public function withActions(array $actions): self
    {
        $actionValues = array_map(fn($action) => $action instanceof AuditAction ? $action->value : $action, $actions);
        $this->query->whereIn('action', $actionValues);
        return $this;
    }

    /**
     * Filter op uitkomst
     */
    public function withOutcome(AuditOutcome $outcome): self
    {
        $this->query->where('outcome', $outcome->value);
        return $this;
    }

    /**
     * Filter op alleen succesvolle acties
     */
    public function onlySuccessful(): self
    {
        return $this->withOutcome(AuditOutcome::SUCCESS);
    }

    /**
     * Filter op alleen gefaalde acties
     */
    public function onlyFailed(): self
    {
        return $this->withOutcome(AuditOutcome::FAILURE);
    }

    /**
     * Filter op een periode
     */
    public function inPeriod(Carbon $from, Carbon $to): self
    {
        $this->query->whereBetween('event_time', [$from, $to]);
        return $this;
    }

    /**
     * Filter vanaf een bepaalde datum
     */
    public function since(Carbon $date): self
    {
        $this->query->where('event_time', '>=', $date);
        return $this;
    }

    /**
     * Filter tot een bepaalde datum
     */
    public function until(Carbon $date): self
    {
        $this->query->where('event_time', '<=', $date);
        return $this;
    }

    /**
     * Filter op vandaag
     */
    public function today(): self
    {
        return $this->inPeriod(
            Carbon::today(),
            Carbon::tomorrow()->subSecond()
        );
    }

    /**
     * Filter on the last X days
     */
    public function lastDays(int $days): self
    {
        return $this->since(Carbon::now()->subDays($days));
    }

    /**
     * Filter on the last week
     */
    public function lastWeek(): self
    {
        return $this->lastDays(7);
    }

    /**
     * Filter on the last month
     */
    public function lastMonth(): self
    {
        return $this->lastDays(30);
    }

    /**
     * Filter on IP address
     */
    public function fromIpAddress(string $ipAddress): self
    {
        $this->query->where('ip_address', $ipAddress);
        return $this;
    }

    /**
     * Filter on source ID
     */
    public function fromSource(string $sourceId): self
    {
        $this->query->where('source_id', $sourceId);
        return $this;
    }

    /**
     * Search in object name (masked)
     */
    public function searchObjectName(string $term): self
    {
        $this->query->where('object_name', 'LIKE', "%{$term}%");
        return $this;
    }

    /**
     * Search in actor name
     */
    public function searchActorName(string $term): self
    {
        $this->query->where('actor_name', 'LIKE', "%{$term}%");
        return $this;
    }

    /**
     * Filter on actions that modify data (CREATE, UPDATE, DELETE)
     */
    public function onlyDataChanges(): self
    {
        return $this->withActions([
            AuditAction::CREATE,
            AuditAction::UPDATE,
            AuditAction::DELETE
        ]);
    }

    /**
     * Filter on READ actions only
     */
    public function onlyReads(): self
    {
        return $this->withAction(AuditAction::READ);
    }

    /**
     * Filter op emergency access
     */
    public function emergencyAccess(): self
    {
        $this->query->where('authorization_protocol', 'emergency_override')
                   ->orWhere('object_detail', 'LIKE', '%emergency_access%');
        return $this;
    }

    /**
     * Filter on actions that have authorization checks
     */
    public function withAuthorization(): self
    {
        $this->query->where('authorization_check', true);
        return $this;
    }

    /**
     * Filter on actions with treatment relation check
     */
    public function withTreatmentRelation(): self
    {
        $this->query->where('treatment_relation_check', true);
        return $this;
    }

    /**
     * Filter on actions with consent check
     */
    public function withConsent(): self
    {
        $this->query->where('consent_check', true);
        return $this;
    }

    /**
     * Sorteer op tijdstip (nieuwste eerst)
     */
    public function latest(): self
    {
        $this->query->orderBy('event_time', 'desc');
        return $this;
    }

    /**
     * Sorteer op tijdstip (oudste eerst)
     */
    public function oldest(): self
    {
        $this->query->orderBy('event_time');
        return $this;
    }

    /**
     * Sorteer op actie type
     */
    public function orderByAction(): self
    {
        $this->query->orderBy('action');
        return $this;
    }

    /**
     * Limiteer resultaten
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Voeg een offset toe
     */
    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * Selecteer specifieke kolommen
     */
    public function select(array $columns): self
    {
        $this->query->select($columns);
        return $this;
    }

    /**
     * Tel het aantal resultaten
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Bestaat er een record dat voldoet aan de criteria
     */
    public function exists(): bool
    {
        return $this->query->exists();
    }

    /**
     * Krijg de eerste record
     */
    public function first(): ?object
    {
        return $this->query->first();
    }

    /**
     * Krijg alle records
     */
    public function get(): \Illuminate\Support\Collection
    {
        return $this->query->get();
    }

    /**
     * Paginate de resultaten
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null)
    {
        return $this->query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Simple paginate de resultaten
     */
    public function simplePaginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null)
    {
        return $this->query->simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Krijg de onderliggende query builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Statistische queries
     */

    /**
     * Krijg statistieken per actie type
     */
    public function actionStats(): \Illuminate\Support\Collection
    {
        return $this->query->selectRaw('action, COUNT(*) as count')
                          ->groupBy('action')
                          ->orderBy('count', 'desc')
                          ->get();
    }

    /**
     * Get statistics per actor
     */
    public function actorStats(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->query->selectRaw('actor_name, actor_role, COUNT(*) as count')
                          ->groupBy('actor_name', 'actor_role')
                          ->orderBy('count', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Get statistics per object type
     */
    public function objectTypeStats(): \Illuminate\Support\Collection
    {
        return $this->query->selectRaw('object_type, COUNT(*) as count')
                          ->groupBy('object_type')
                          ->orderBy('count', 'desc')
                          ->get();
    }

    /**
     * Get daily statistics
     */
    public function dailyStats(int $days = 30): \Illuminate\Support\Collection
    {
        return $this->query->selectRaw('DATE(event_time) as date, COUNT(*) as count')
                          ->where('event_time', '>=', Carbon::now()->subDays($days))
                          ->groupBy('date')
                          ->orderBy('date', 'desc')
                          ->get();
    }
}
<?php

namespace CbyteDigital\Nen7513AuditLogging\Services;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditAction;
use CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\StateSnapshot;
use Illuminate\Database\Eloquent\Model;

/**
 * AuditLogService - Fluent interface for audit logging
 * 
 * Provides a clean, fluent API for logging audit events.
 * Use via the AuditLog facade for the best developer experience.
 */
class AuditLogService
{
    private ?Model $actor = null;
    private ?AuditContext $context = null;
    private ?StateSnapshot $originalState = null;
    
    public function __construct(
        private AuditLogger $auditLogger,
        private ActorResolver $actorResolver,
        private AuditContextFactory $contextFactory
    ) {}

    /**
     * Set the actor for this audit action
     */
    public function forActor(Model $actor): self
    {
        $clone = clone $this;
        $clone->actor = $actor;
        return $clone;
    }

    /**
     * Set a complete context
     */
    public function withContext(
        ?array $detail = null,
        ?AuditOutcome $outcome = null,
        ?string $authProtocol = null,
        ?bool $authorizationCheck = null,
        ?bool $treatmentRelationCheck = null,
        ?bool $consentCheck = null,
        ?string $sourceId = null,
        ?string $sourceType = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        $clone = clone $this;
        $clone->context = new AuditContext(
            detail: $detail,
            outcome: $outcome ?? AuditOutcome::SUCCESS,
            authProtocol: $authProtocol ?? 'default',
            authorizationCheck: $authorizationCheck ?? true,
            treatmentRelationCheck: $treatmentRelationCheck ?? false,
            consentCheck: $consentCheck ?? false,
            sourceId: $sourceId,
            sourceType: $sourceType,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
        return $clone;
    }

    /**
     * Add detail information
     */
    public function withDetail(array $detail): self
    {
        $clone = clone $this;
        $clone->context = $this->contextFactory->createDefault($detail);
        return $clone;
    }

    /**
     * Mark as failed action
     */
    public function failed(?string $reason = null): self
    {
        $clone = clone $this;
        $clone->context = $this->contextFactory->createFailure($reason);
        return $clone;
    }

    /**
     * Mark as successful action with details
     */
    public function successful(array $detail = []): self
    {
        $clone = clone $this;
        $clone->context = $this->contextFactory->createDefault($detail);
        return $clone;
    }

    /**
     * Emergency access context
     */
    public function emergency(string $reason, string $authorizedBy): self
    {
        $clone = clone $this;
        $clone->context = $this->contextFactory->createEmergency($reason, $authorizedBy);
        return $clone;
    }

    /**
     * Full authorization controls
     */
    public function withFullAuthorization(
        string $authProtocol = 'oauth2',
        bool $treatmentRelation = true,
        bool $consent = true
    ): self {
        $clone = clone $this;
        $clone->context = $this->contextFactory->createWithFullAuthorization($authProtocol, $treatmentRelation, $consent);
        return $clone;
    }

    /**
     * Set authorization check flag
     */
    public function withAuthorization(bool $authorized = true): self
    {
        $clone = clone $this;
        $currentContext = $clone->context ?? $this->contextFactory->createDefault();
        
        $clone->context = new AuditContext(
            detail: $currentContext->detail,
            outcome: $currentContext->outcome,
            authProtocol: $currentContext->authProtocol,
            authorizationCheck: $authorized,
            treatmentRelationCheck: $currentContext->treatmentRelationCheck,
            consentCheck: $currentContext->consentCheck,
            sourceId: $currentContext->sourceId,
            sourceType: $currentContext->sourceType,
            ipAddress: $currentContext->ipAddress,
            userAgent: $currentContext->userAgent
        );
        return $clone;
    }

    /**
     * Set treatment relation check flag
     */
    public function withTreatmentRelation(bool $hasRelation = true): self
    {
        $clone = clone $this;
        $currentContext = $clone->context ?? $this->contextFactory->createDefault();
        
        $clone->context = new AuditContext(
            detail: $currentContext->detail,
            outcome: $currentContext->outcome,
            authProtocol: $currentContext->authProtocol,
            authorizationCheck: $currentContext->authorizationCheck,
            treatmentRelationCheck: $hasRelation,
            consentCheck: $currentContext->consentCheck,
            sourceId: $currentContext->sourceId,
            sourceType: $currentContext->sourceType,
            ipAddress: $currentContext->ipAddress,
            userAgent: $currentContext->userAgent
        );
        return $clone;
    }

    /**
     * Set consent check flag
     */
    public function withConsent(bool $hasConsent = true): self
    {
        $clone = clone $this;
        $currentContext = $clone->context ?? $this->contextFactory->createDefault();
        
        $clone->context = new AuditContext(
            detail: $currentContext->detail,
            outcome: $currentContext->outcome,
            authProtocol: $currentContext->authProtocol,
            authorizationCheck: $currentContext->authorizationCheck,
            treatmentRelationCheck: $currentContext->treatmentRelationCheck,
            consentCheck: $hasConsent,
            sourceId: $currentContext->sourceId,
            sourceType: $currentContext->sourceType,
            ipAddress: $currentContext->ipAddress,
            userAgent: $currentContext->userAgent
        );
        return $clone;
    }

    /**
     * Capture the current state of a model and its relationships
     * Use this before making changes to track what changed
     */
    public static function originalState(Model $model, array $relations = []): StateSnapshot
    {
        return StateSnapshot::capture($model, $relations);
    }

    /**
     * Set the original state for comparison during UPDATE logging
     * Use this with a StateSnapshot captured before changes
     */
    public function withOriginalState(StateSnapshot $originalState): self
    {
        $clone = clone $this;
        $clone->originalState = $originalState;
        return $clone;
    }

    /**
     * Log a CREATE action
     */
    public function created($object, ?string $name = null): void
    {
        $this->log(AuditAction::CREATE, $object, $name);
    }

    /**
     * Log a READ action
     */
    public function read($object, ?string $name = null): void
    {
        $this->log(AuditAction::READ, $object, $name);
    }

    /**
     * Log an UPDATE action
     */
    public function updated($object, array $changedFields = [], ?string $name = null): void
    {
        // If we have an original state, use it to generate detailed comparison
        if ($this->originalState && $object instanceof Model) {
            $comparison = $this->originalState->compareWith($object);
            
            $detail = [
                'operation' => 'update_with_state_comparison',
                'captured_at' => $this->originalState->capturedAt->format('Y-m-d H:i:s'),
                'has_changes' => $comparison['has_changes'],
            ];

            // Add model changes (clean format)
            if (!empty($comparison['model_changes'])) {
                $detail['model_changes'] = $comparison['model_changes'];
                $detail['changed_fields'] = array_keys($comparison['model_changes']);
            }

            // Add relationship changes (without summary duplication)
            if (!empty($comparison['relationship_changes'])) {
                $detail['relationship_changes'] = $comparison['relationship_changes'];
            }

            $this->context = $this->contextFactory->createDefault($detail);
        } else {
            // Fallback to simple context if no original state
            if (!$this->context) {
                $this->context = $this->contextFactory->createForUpdate($changedFields);
            }
        }
        
        $this->log(AuditAction::UPDATE, $object, $name);
    }

    /**
     * Log a DELETE action
     */
    public function deleted($object, ?string $name = null): void
    {
        $this->log(AuditAction::DELETE, $object, $name);
    }

    /**
     * Log an EXECUTE action
     */
    public function execute($object, ?string $name = null): void
    {
        $this->log(AuditAction::EXECUTE, $object, $name);
    }

    /**
     * Convenience method: Log with model changes
     */
    public function updatedWithChanges($object, array $oldValues = [], array $newValues = [], ?string $name = null): void
    {
        $changedFields = array_keys($newValues);
        
        $this->context = $this->contextFactory->createForUpdate(
            changedFields: $changedFields,
            oldValues: $oldValues,
            newValues: $newValues
        );
        
        $this->log(AuditAction::UPDATE, $object, $name);
    }

    /**
     * Bulk logging - log multiple events at once
     */
    public function bulk(array $events): void
    {
        $auditEvents = [];
        
        foreach ($events as $event) {
            $auditEvents[] = [
                'action' => $event['action'],
                'actor' => $event['actor'] ?? $this->actor ?? $this->getDefaultActor(),
                'object_type' => is_object($event['object']) ? $event['object']->getTable() : ($event['object_type'] ?? 'unknown'),
                'object_id' => is_object($event['object']) ? $event['object']->getKey() : $event['object_id'],
                'object_name' => $event['name'] ?? (is_object($event['object']) && method_exists($event['object'], 'getAuditName') ? $event['object']->getAuditName() : null),
                'context' => $event['context'] ?? $this->context ?? $this->contextFactory->createDefault(),
            ];
        }
        
        $this->auditLogger->logBulk($auditEvents);
    }

    /**
     * Internal method for executing the log action
     */
    private function log(AuditAction $action, $object, ?string $name): void
    {
        $actor = $this->actor ?? $this->getDefaultActor();
        
        if (!$actor) {
            // No actor available, skip logging
            return;
        }
        
        // Determine object information
        if (is_object($object)) {
            $objectType = method_exists($object, 'getTable') ? $object->getTable() : get_class($object);
            $objectId = method_exists($object, 'getKey') ? (string) $object->getKey() : 'unknown';
            $objectName = $name ?? (method_exists($object, 'getAuditName') ? $object->getAuditName() : null);
        } else {
            // Primitive value
            $objectType = 'unknown';
            $objectId = (string) $object;
            $objectName = $name;
        }
        
        $this->auditLogger->log(
            $action,
            $actor,
            $objectType,
            $objectId,
            $objectName,
            $this->context ?? $this->contextFactory->createDefault()
        );
        
        // Reset context, actor, and original state for next call
        $this->context = null;
        $this->actor = null;
        $this->originalState = null;
    }

    /**
     * Get the default actor
     */
    private function getDefaultActor(): ?Model
    {
        return $this->actorResolver->resolve();
    }
}
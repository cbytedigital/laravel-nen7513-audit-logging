<?php

namespace CbyteDigital\Nen7513AuditLogging\Services;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditAction;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * AuditLogger - Main service for logging audit events
 * 
 * Implements NEN 7513-compliant logging for healthcare applications.
 * All mandatory fields according to the standard are correctly filled.
 */
class AuditLogger
{
    public function __construct(
        private ActorResolver $actorResolver
    ) {}

    /**
     * Log an audit event to the database
     */
    public function log(
        AuditAction $action,
        Model $actor,
        string $objectType,
        string $objectId,
        ?string $objectName = null,
        ?AuditContext $context = null
    ): void {
        // Validate that actor has the required methods
        $this->validateActor($actor);
        
        $context ??= new AuditContext();
        
        // Get request information
        $request = request();
        $ipAddress = $context->ipAddress ?? $request?->ip();
        $userAgent = $context->userAgent ?? $request?->userAgent();
        
        // Determine source information
        $sourceId = $context->sourceId ?? config('audit-log.source.default_id', config('app.name', 'laravel_app'));
        $sourceType = $context->sourceType ?? config('audit-log.source.default_type', 'laravel_application');
        
        // Prepare audit record data
        $auditData = [
            // Event information (NEN 7513 mandatory)
            'action' => $action->value,
            'event_time' => now(),
            'outcome' => $context->outcome->value,
            
            // Actor information (polymorphic)
            'actor_type' => get_class($actor),
            'actor_id' => $actor->getKey(),
            'actor_name' => $actor->getAuditActorName(),
            'actor_role' => $actor->getAuditActorRole(),
            
            // Object information (NEN 7513 mandatory)
            'object_type' => $objectType,
            'object_id' => $objectId,
            'object_name' => $objectName,
            'object_detail' => $context->detail ? json_encode($context->detail) : null,
            
            // Authorization information (NEN 7513 mandatory)
            'authorization_protocol' => $context->authProtocol,
            'authorization_check' => $context->authorizationCheck,
            'treatment_relation_check' => $context->treatmentRelationCheck,
            'consent_check' => $context->consentCheck,
            
            // Source information (NEN 7513 mandatory)
            'source_id' => $sourceId,
            'source_type' => $sourceType,
            
            // Context information
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ];
        
        // Calculate integrity hash for NEN 7513 compliance
        $auditData['integrity_hash'] = $this->calculateIntegrityHash($auditData);
        
        // Insert audit log record
        DB::table(config('audit-log.table_name', 'audit_logs'))->insert($auditData);
    }

    /**
     * Log a CREATE action
     */
    public function logCreate(
        Model $actor,
        string $objectType,
        string $objectId,
        ?string $objectName = null,
        ?AuditContext $context = null
    ): void {
        $this->log(AuditAction::CREATE, $actor, $objectType, $objectId, $objectName, $context);
    }

    /**
     * Log a READ action
     */
    public function logRead(
        Model $actor,
        string $objectType,
        string $objectId,
        ?string $objectName = null,
        ?AuditContext $context = null
    ): void {
        $this->log(AuditAction::READ, $actor, $objectType, $objectId, $objectName, $context);
    }

    /**
     * Log an UPDATE action
     */
    public function logUpdate(
        Model $actor,
        string $objectType,
        string $objectId,
        ?string $objectName = null,
        ?AuditContext $context = null,
        array $changedFields = []
    ): void {
        // Add changed fields to context if not already present
        if (!empty($changedFields) && (!$context || !$context->hasDetail())) {
            $context = AuditContext::forUpdate($changedFields);
        }
        
        $this->log(AuditAction::UPDATE, $actor, $objectType, $objectId, $objectName, $context);
    }

    /**
     * Log a DELETE action
     */
    public function logDelete(
        Model $actor,
        string $objectType,
        string $objectId,
        ?string $objectName = null,
        ?AuditContext $context = null
    ): void {
        $this->log(AuditAction::DELETE, $actor, $objectType, $objectId, $objectName, $context);
    }

    /**
     * Log an EXECUTE action
     */
    public function logExecute(
        Model $actor,
        string $objectType,
        string $objectId,
        ?string $objectName = null,
        ?AuditContext $context = null
    ): void {
        $this->log(AuditAction::EXECUTE, $actor, $objectType, $objectId, $objectName, $context);
    }

    /**
     * Convenience method: log action based on a model
     */
    public function logForModel(
        AuditAction $action,
        Model $actor,
        Model $object,
        ?AuditContext $context = null
    ): void {
        $objectName = null;
        
        // Try to get object name
        if (method_exists($object, 'getAuditName')) {
            $objectName = $object->getAuditName();
        }
        
        $this->log(
            $action,
            $actor,
            $object->getTable(),
            (string) $object->getKey(),
            $objectName,
            $context
        );
    }

    /**
     * Validate that actor model has the required methods
     */
    private function validateActor(Model $actor): void
    {
        $this->actorResolver->validateActor($actor);
    }



    /**
     * Bulk log multiple events (performance optimization)
     */
    public function logBulk(array $events): void
    {
        $records = [];
        
        foreach ($events as $event) {
            $this->validateActor($event['actor']);
            
            $context = $event['context'] ?? new AuditContext();
            $request = request();
            
            $records[] = [
                // Event information
                'action' => $event['action']->value,
                'event_time' => now(),
                'outcome' => $context->outcome->value,
                
                // Actor information
                'actor_type' => get_class($event['actor']),
                'actor_id' => $event['actor']->getKey(),
                'actor_name' => $event['actor']->getAuditActorName(),
                'actor_role' => $event['actor']->getAuditActorRole(),
                
                // Object information
                'object_type' => $event['object_type'],
                'object_id' => $event['object_id'],
                'object_name' => $event['object_name'] ?? null,
                'object_detail' => $context->detail ? json_encode($context->detail) : null,
                
                // Authorization information
                'authorization_protocol' => $context->authProtocol,
                'authorization_check' => $context->authorizationCheck,
                'treatment_relation_check' => $context->treatmentRelationCheck,
                'consent_check' => $context->consentCheck,
                
                // Source information
                'source_id' => $context->sourceId ?? config('audit-log.source.default_id', config('app.name')),
                'source_type' => $context->sourceType ?? config('audit-log.source.default_type', 'laravel_application'),
                
                // Context information
                'ip_address' => $context->ipAddress ?? $request?->ip(),
                'user_agent' => $context->userAgent ?? $request?->userAgent(),
            ];
        }
        
        DB::table(config('audit-log.table_name', 'audit_logs'))->insert($records);
    }

    /**
     * Calculate integrity hash for NEN 7513 compliance
     * 
     * Creates a SHA-256 hash of critical audit data to ensure data integrity
     */
    private function calculateIntegrityHash(array $auditData): string
    {
        // Use only critical fields for hash calculation (exclude dynamic fields)
        $criticalData = [
            'action' => $auditData['action'],
            'event_time' => $auditData['event_time']->toISOString(),
            'outcome' => $auditData['outcome'],
            'actor_type' => $auditData['actor_type'],
            'actor_id' => $auditData['actor_id'],
            'actor_name' => $auditData['actor_name'],
            'object_type' => $auditData['object_type'],
            'object_id' => $auditData['object_id'],
            'object_name' => $auditData['object_name'],
            'object_detail' => $auditData['object_detail'],
            'authorization_protocol' => $auditData['authorization_protocol'],
            'source_id' => $auditData['source_id'],
        ];
        
        // Create deterministic string for hashing (sort keys for consistency)
        ksort($criticalData);
        $hashString = json_encode($criticalData);
        
        // Add secret key from config for additional security
        $secret = config('audit-log.integrity_secret', config('app.key'));
        
        return hash('sha256', $hashString . $secret);
    }
}
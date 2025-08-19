<?php

namespace CbyteDigital\Nen7513AuditLogging\Traits;

/**
 * AuditActor Trait
 * 
 * This trait must be used by User/Actor models that perform audit logging.
 * It defines the required methods that the AuditLogger expects.
 * 
 * Models using this trait MUST implement the abstract methods.
 */
trait AuditActor
{
    /**
     * Required method: return the name for audit logging
     * 
     * This name is stored in the audit log for historical reference.
     * Use a name that remains usable even if the user is changed later.
     * 
     * @return string The actor's name for audit purposes
     */
    abstract public function getAuditActorName(): string;
    
    /**
     * Required method: return the role for audit logging
     * 
     * This role is used to understand the authorization context.
     * Examples: 'admin', 'doctor', 'nurse', 'patient', 'system'
     * 
     * @return string The actor's role for audit purposes
     */
    abstract public function getAuditActorRole(): string;
    
    /**
     * Optional method: return unique identifier for audit logging
     * 
     * NEN 7513 requires unique actor identification.
     * By default, uses the model's primary key, but can be overridden
     * for custom unique identifiers (e.g., employee_id, bsn, etc)
     * 
     * @return string|int Unique identifier for this actor
     */
    public function getAuditActorId(): string|int
    {
        return $this->getKey();
    }
}
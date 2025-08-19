<?php

namespace CbyteDigital\Nen7513AuditLogging\Actors;

use CbyteDigital\Nen7513AuditLogging\Traits\AuditActor;
use Illuminate\Database\Eloquent\Model;

/**
 * SystemActor
 * 
 * Default system actor for console commands and automated processes.
 * Used for audit logging when there is no authenticated user.
 */
class SystemActor extends Model
{
    use AuditActor;

    protected $fillable = [];
    public $timestamps = false;
    
    /**
     * System actor always has ID 0
     */
    public function getKey()
    {
        return 0;
    }

    /**
     * Get the name for audit logging
     */
    public function getAuditActorName(): string
    {
        return 'System';
    }

    /**
     * Get the role for audit logging
     */
    public function getAuditActorRole(): string
    {
        return 'system';
    }
}
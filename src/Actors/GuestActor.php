<?php

namespace CbyteDigital\Nen7513AuditLogging\Actors;

use CbyteDigital\Nen7513AuditLogging\Traits\AuditActor;
use Illuminate\Database\Eloquent\Model;

/**
 * GuestActor
 * 
 * Actor for unauthenticated website visitors.
 * Used for audit logging when there is no authenticated user but someone is using the website.
 */
class GuestActor extends Model
{
    use AuditActor;

    protected $fillable = [];
    public $timestamps = false;
    
    private string $sessionId;
    private string $ipAddress;
    private string $userAgent;
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        $this->sessionId = session()->getId() ?: 'no-session';
        $this->ipAddress = request()?->ip() ?: 'unknown';
        $this->userAgent = request()?->userAgent() ?: 'unknown';
    }
    
    /**
     * Guest actor uses session ID as key for tracking
     */
    public function getKey()
    {
        return 'guest_' . substr($this->sessionId, 0, 8);
    }

    /**
     * Get the name for audit logging
     */
    public function getAuditActorName(): string
    {
        return 'Guest User (' . substr($this->sessionId, 0, 8) . ')';
    }

    /**
     * Get the role for audit logging
     */
    public function getAuditActorRole(): string
    {
        return 'guest';
    }
}
<?php

namespace CbyteDigital\Nen7513AuditLogging\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

/**
 * ActorResolver
 * 
 * Centralized service for resolving audit actors.
 * Eliminates duplicate actor detection logic across the package.
 */
class ActorResolver
{
    public function __construct(
        private Application $app
    ) {}

    /**
     * Resolve the current audit actor
     * 
     * @param Model|null $actor Optional specific actor to use, if null uses automatic detection
     * @return Model|null The actor performing the action, or null if none available
     */
    public function resolve(?Model $actor = null): ?Model
    {
        // If specific actor provided, use that
        if ($actor) {
            return $actor;
        }
        
        // Try authenticated user first
        $user = auth()->user();
        if ($user && $this->isValidActor($user)) {
            return $user;
        }
        
        // Determine context and fallback actor
        if ($this->app->runningInConsole()) {
            // Console context: use system actor
            return $this->getSystemActor();
        } else {
            // Web context but not authenticated: use guest actor
            return $this->getGuestActor();
        }
    }

    /**
     * Check if a model is a valid audit actor
     * 
     * @param Model $actor The potential actor model
     * @return bool True if the model implements required actor methods
     */
    public function isValidActor(Model $actor): bool
    {
        return method_exists($actor, 'getAuditActorName') 
            && method_exists($actor, 'getAuditActorRole');
    }

    /**
     * Validate that an actor has required methods, throw exception if not
     * 
     * @param Model $actor The actor to validate
     * @throws \InvalidArgumentException If actor is invalid
     */
    public function validateActor(Model $actor): void
    {
        if (!method_exists($actor, 'getAuditActorName')) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Actor model %s must implement getAuditActorName() method. Use AuditActor trait or implement manually.',
                    get_class($actor)
                )
            );
        }
        
        if (!method_exists($actor, 'getAuditActorRole')) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Actor model %s must implement getAuditActorRole() method. Use AuditActor trait or implement manually.',
                    get_class($actor)
                )
            );
        }
    }

    /**
     * Get system actor if bound
     * 
     * @return Model|null
     */
    public function getSystemActor(): ?Model
    {
        if ($this->app->bound('audit.system_actor')) {
            return $this->app->make('audit.system_actor');
        }
        
        return null;
    }

    /**
     * Get guest actor if bound
     * 
     * @return Model|null  
     */
    public function getGuestActor(): ?Model
    {
        if ($this->app->bound('audit.guest_actor')) {
            return $this->app->make('audit.guest_actor');
        }
        
        return null;
    }


}
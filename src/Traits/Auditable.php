<?php

namespace CbyteDigital\Nen7513AuditLogging\Traits;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditAction;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;
use CbyteDigital\Nen7513AuditLogging\Services\AuditLogger;

/**
 * Auditable Trait
 * 
 * This trait adds automatic audit logging to Eloquent models.
 * It listens to model events and logs CRUD operations automatically.
 * 
 * Models using this trait will be automatically audited when:
 * - retrieved (READ) - optional, can be disabled
 * - created (CREATE) - with optional full data logging
 * - updated (UPDATE) - including which fields were changed
 * - deleted (DELETE)
 */
trait Auditable
{
    /**
     * Boot the Auditable trait
     * Registers event listeners for automatic audit logging
     */
    public static function bootAuditable(): void
    {
        $auditableActions = static::getAuditableActions();
        
        // READ events (configurable)
        if ($auditableActions['read'] ?? false) {
            static::retrieved(function ($model) {
                if (static::auditingDisabled()) {
                    return;
                }
                
                $actor = static::getAuditActor();
                if ($actor) {
                    app(AuditLogger::class)->logRead(
                        $actor,
                        $model->getTable(),
                        (string) $model->getKey(),
                        $model->getAuditName()
                    );
                }
            });
        }
        
        // CREATE events (configurable)
        if ($auditableActions['create'] ?? true) {
            static::created(function ($model) {
                if (static::auditingDisabled()) {
                    return;
                }
                
                $actor = static::getAuditActor();
                if ($actor) {
                    $context = null;
                    
                    // Check if we should log full CREATE data
                    if ($model->shouldLogCreateData()) {
                        $contextFactory = app(\CbyteDigital\Nen7513AuditLogging\Services\AuditContextFactory::class);
                        $context = $contextFactory->createForCreate(
                            $model->getAuditCreateData()
                        );
                    }
                    
                    app(AuditLogger::class)->logCreate(
                        $actor,
                        $model->getTable(),
                        (string) $model->getKey(),
                        $model->getAuditName(),
                        $context
                    );
                }
            });
        }
        
        // UPDATE events (configurable)
        if ($auditableActions['update'] ?? true) {
            static::updated(function ($model) {
                if (static::auditingDisabled()) {
                    return;
                }
                
                $actor = static::getAuditActor();
                if ($actor) {
                    $changed = $model->getDirty();
                    $original = $model->getOriginal();
                    
                    // Filter out timestamps if configured
                    if (static::shouldIgnoreTimestamps()) {
                        $changed = array_diff_key($changed, array_flip(['created_at', 'updated_at']));
                    }
                    
                    // Skip if no relevant changes
                    if (empty($changed)) {
                        return;
                    }
                    
                    $contextFactory = app(\CbyteDigital\Nen7513AuditLogging\Services\AuditContextFactory::class);
                    $context = $contextFactory->createForUpdate(
                        changedFields: array_keys($changed),
                        oldValues: array_intersect_key($original, $changed),
                        newValues: $changed
                    );
                    
                    app(AuditLogger::class)->logUpdate(
                        $actor,
                        $model->getTable(),
                        (string) $model->getKey(),
                        $model->getAuditName(),
                        $context
                    );
                }
            });
        }
        
        // DELETE events (configurable)
        if ($auditableActions['delete'] ?? true) {
            static::deleted(function ($model) {
                if (static::auditingDisabled()) {
                    return;
                }
                
                $actor = static::getAuditActor();
                if ($actor) {
                    app(AuditLogger::class)->logDelete(
                        $actor,
                        $model->getTable(),
                        (string) $model->getKey(),
                        $model->getAuditName()
                    );
                }
            });
        }
    }
    
    /**
     * Get the name for audit logging
     * Override this method to provide a specific name
     * 
     * @return string|null The name of the object for audit purposes
     */
    public function getAuditName(): ?string
    {
        // Try different fields
        return $this->name 
            ?? $this->title 
            ?? $this->display_name 
            ?? $this->email 
            ?? null;
    }
    
    /**
     * Configure which CRUD actions should be audited
     * Override this method to customize audit behavior per model
     * 
     * @return array Array of actions and whether they should be audited
     */
    protected static function getAuditableActions(): array
    {
        return [
            'read' => config('audit-log.default_audit.read', false),
            'create' => config('audit-log.default_audit.create', true),
            'update' => config('audit-log.default_audit.update', true),
            'delete' => config('audit-log.default_audit.delete', true),
        ];
    }
    
    /**
     * Should this model log full data on CREATE?
     * 
     * @return bool
     */
    protected function shouldLogCreateData(): bool
    {
        return config('audit-log.default_audit.log_create_data', true);
    }
    
    /**
     * Get the data to log when model is created
     * Override to customize what gets logged on CREATE
     * 
     * @return array
     */
    protected function getAuditCreateData(): array
    {
        $data = $this->toArray();
        
        // Apply exclusions
        $excluded = static::getAuditExcludedFields();
        foreach ($excluded as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    /**
     * Check if timestamps should be ignored during updates
     * 
     * @return bool True if created_at/updated_at should be ignored
     */
    protected static function shouldIgnoreTimestamps(): bool
    {
        return true; // Default: ignore timestamp-only updates
    }
    
    /**
     * Get the audit actor (user performing the action)
     * Override to implement custom logic
     * 
     * @return \Illuminate\Database\Eloquent\Model|null The actor performing the action
     */
    protected static function getAuditActor()
    {
        return app(\CbyteDigital\Nen7513AuditLogging\Services\ActorResolver::class)->resolve();
    }
    
    /**
     * Fields that should not be audited during updates
     * Override to configure per model
     * 
     * @return array Fields that should be excluded from audit
     */
    protected static function getAuditExcludedFields(): array
    {
        return [
            'created_at',
            'updated_at',
            'deleted_at',
            'password',
            'remember_token',
        ];
    }
    
    /**
     * Manual audit logging - for custom events
     */
    public function auditExecute(string $action, ?AuditContext $context = null, ?string $customName = null): void
    {
        $actor = static::getAuditActor();
        if ($actor) {
            app(AuditLogger::class)->logExecute(
                $actor,
                $this->getTable(),
                (string) $this->getKey(),
                $customName ?? $this->getAuditName(),
                $context
            );
        }
    }
    
    /**
     * Manual audit logging - for custom CREATE events
     */
    public function auditCreate(?AuditContext $context = null): void
    {
        $actor = static::getAuditActor();
        if ($actor) {
            app(AuditLogger::class)->logCreate(
                $actor,
                $this->getTable(),
                (string) $this->getKey(),
                $this->getAuditName(),
                $context
            );
        }
    }

    /**
     * Manual audit logging - for custom READ events
     */
    public function auditRead(?AuditContext $context = null): void
    {
        $actor = static::getAuditActor();
        if ($actor) {
            app(AuditLogger::class)->logRead(
                $actor,
                $this->getTable(),
                (string) $this->getKey(),
                $this->getAuditName(),
                $context
            );
        }
    }

    /**
     * Manual audit logging - for custom UPDATE events
     */
    public function auditUpdate(?AuditContext $context = null): void
    {
        $actor = static::getAuditActor();
        if ($actor) {
            app(AuditLogger::class)->logUpdate(
                $actor,
                $this->getTable(),
                (string) $this->getKey(),
                $this->getAuditName(),
                $context
            );
        }
    }

    /**
     * Manual audit logging - for custom DELETE events
     */
    public function auditDelete(?AuditContext $context = null): void
    {
        $actor = static::getAuditActor();
        if ($actor) {
            app(AuditLogger::class)->logDelete(
                $actor,
                $this->getTable(),
                (string) $this->getKey(),
                $this->getAuditName(),
                $context
            );
        }
    }
    
    /**
     * Disable audit logging for this instance
     */
    public function withoutAuditing(\Closure $callback)
    {
        static::$auditingDisabled = true;
        
        try {
            return $callback();
        } finally {
            static::$auditingDisabled = false;
        }
    }
    
    /**
     * Check if auditing is disabled
     */
    protected static function auditingDisabled(): bool
    {
        return static::$auditingDisabled ?? false;
    }
    
    /**
     * Internal flag for disabling auditing
     */
    protected static bool $auditingDisabled = false;
}
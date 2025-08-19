<?php

namespace CbyteDigital\Nen7513AuditLogging\Traits;

use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;
use CbyteDigital\Nen7513AuditLogging\Services\AuditContextFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ParentAuditable Trait
 * 
 * This trait enables child models to automatically log changes on their parent models
 * while preserving all the rich context information about what actually changed.
 * 
 * Perfect for scenarios like:
 * - ClientHistory changes -> log on Client
 * - Bestelregel changes -> log on Bestellijst  
 * - AssessmentAnswer changes -> log on Assessment
 */
trait ParentAuditable
{
    /**
     * Boot the ParentAuditable trait
     * Automatically sets up parent logging for CUD operations
     */
    public static function bootParentAuditable(): void
    {
        try {
            $parentConfig = static::getParentAuditConfig();
        } catch (\BadMethodCallException $e) {
            // Configuration not provided - skip parent auditing
            return;
        }
        
        if (!$parentConfig) {
            return; // Parent audit disabled
        }
        
        // Merge with defaults
        $defaults = static::getDefaultParentAuditConfig();
        $config = array_merge($defaults, $parentConfig);
        
        // CREATE events
        if ($config['actions']['create'] ?? true) {
            static::created(function ($model) use ($config) {
                $model->auditOnParent('created', $config);
            });
        }
        
        // UPDATE events  
        if ($config['actions']['update'] ?? true) {
            static::updated(function ($model) use ($config) {
                $model->auditOnParent('updated', $config);
            });
        }
        
        // DELETE events
        if ($config['actions']['delete'] ?? true) {
            static::deleted(function ($model) use ($config) {
                $model->auditOnParent('deleted', $config);
            });
        }
    }
    
    /**
     * Configure parent audit behavior
     * Override this method in your model to customize
     * 
     * @return array|null Configuration array or null to disable
     */
    protected static function getParentAuditConfig(): ?array
    {
        // MUST be overridden in your model - no sensible defaults possible
        throw new \BadMethodCallException(
            'You must override getParentAuditConfig() in ' . static::class . 
            ' to specify which parent relationship to use for audit logging.'
        );
    }
    
    /**
     * Get default parent audit configuration
     * Used as base configuration that can be merged with model-specific config
     * 
     * @return array Default configuration
     */
    protected static function getDefaultParentAuditConfig(): array
    {
        return [
            'actions' => [
                'create' => true,  // Log when child is created
                'update' => true,  // Log when child is updated
                'delete' => true,  // Log when child is deleted
            ],
            'context_fields' => [],     // Empty = include all fields/changes
            'child_name' => null,       // null = auto-detect using getChildAuditName()
            'include_timestamps' => false, // Whether to include created_at/updated_at in context
        ];
    }
    
    /**
     * Perform the actual parent audit logging
     * 
     * @param string $action The action that was performed (created/updated/deleted)
     * @param array $config The parent audit configuration
     */
    protected function auditOnParent(string $action, array $config): void
    {
        $parentRelation = $config['parent_relation'];
        $parent = $this->$parentRelation;
        
        if (!$parent || !method_exists($parent, 'auditUpdate')) {
            return; // Parent not found or not auditable
        }
        
        // Build rich context about what changed
        $context = $this->buildParentAuditContext($action, $config);
        
        // Log on the parent with rich context
        $parent->auditUpdate($context);
    }
    
    /**
     * Build rich audit context for parent logging
     * 
     * @param string $action The action performed
     * @param array $config The parent audit configuration  
     * @return AuditContext Rich context with child change details
     */
    protected function buildParentAuditContext(string $action, array $config): AuditContext
    {
        $childName = $config['child_name'] ?? $this->getChildAuditName();
        $contextFields = $config['context_fields'];
        
        $contextData = [
            'child_action' => $action,
            'child_type' => class_basename($this),
            'child_id' => $this->getKey(),
            'child_name' => $childName,
        ];
        
        // Add change details for updates
        if ($action === 'updated') {
            $changed = $this->getDirty();
            $original = $this->getOriginal();
            
            // Remove timestamps if not included
            if (!($config['include_timestamps'] ?? false)) {
                $changed = array_diff_key($changed, array_flip(['created_at', 'updated_at']));
                $original = array_diff_key($original, array_flip(['created_at', 'updated_at']));
            }
            
            // Filter context fields if specified
            if (!empty($contextFields)) {
                $changed = array_intersect_key($changed, array_flip($contextFields));
                $original = array_intersect_key($original, array_flip($contextFields));
            }
            
            $contextData['changed_fields'] = array_keys($changed);
            $contextData['old_values'] = array_intersect_key($original, $changed);
            $contextData['new_values'] = $changed;
        }
        
        // Add current field values for creates/deletes
        if ($action === 'created' || $action === 'deleted') {
            $fields = !empty($contextFields) ? 
                array_intersect_key($this->getAttributes(), array_flip($contextFields)) :
                $this->getAttributes();
                
            $contextData['field_values'] = $fields;
        }
        
        return new AuditContext($contextData);
    }
    
    /**
     * Get a descriptive name for this child model
     * Override to customize how this child appears in audit logs
     * 
     * @return string|null Descriptive name for audit context
     */
    protected function getChildAuditName(): ?string
    {
        // Try common name fields
        return $this->name 
            ?? $this->title 
            ?? $this->question
            ?? $this->product_naam
            ?? "#{$this->getKey()}";
    }
    
    /**
     * Manual parent audit logging
     * Use this for custom events or when you need more control
     * 
     * @param string $customAction Custom action name
     * @param array $additionalContext Additional context data
     */
    public function auditParentAction(string $customAction, array $additionalContext = []): void
    {
        $config = static::getParentAuditConfig();
        if (!$config) return;
        
        $parentRelation = $config['parent_relation'];
        $parent = $this->$parentRelation;
        
        if (!$parent || !method_exists($parent, 'auditUpdate')) {
            return;
        }
        
        $contextData = array_merge([
            'child_action' => $customAction,
            'child_type' => class_basename($this),
            'child_id' => $this->getKey(),
            'child_name' => $this->getChildAuditName(),
        ], $additionalContext);
        
        $context = new AuditContext($contextData);
        $parent->auditUpdate($context);
    }
}

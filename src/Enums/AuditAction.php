<?php

namespace CbyteDigital\Nen7513AuditLogging\Enums;

/**
 * Audit Action Types according to NEN 7513
 * 
 * Defines the allowed actions for audit logging:
 * - CREATE: Create new data
 * - READ: Read/view data
 * - UPDATE: Modify existing data
 * - DELETE: Delete data
 * - EXECUTE: Execute actions/procedures
 */
enum AuditAction: string
{
    case CREATE = 'C';
    case READ = 'R';
    case UPDATE = 'U';
    case DELETE = 'D';
    case EXECUTE = 'E';

    /**
     * Get a readable description of the action
     */
    public function description(): string
    {
        return match($this) {
            self::CREATE => 'Create',
            self::READ => 'Read',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
            self::EXECUTE => 'Execute',
        };
    }

    /**
     * Check if this action represents a data modification
     */
    public function isDataChange(): bool
    {
        return in_array($this, [self::CREATE, self::UPDATE, self::DELETE]);
    }

    /**
     * Check if this action represents a read operation
     */
    public function isReadAction(): bool
    {
        return $this === self::READ;
    }
}
<?php

namespace CbyteDigital\Nen7513AuditLogging\Enums;

/**
 * Audit Outcome Types according to NEN 7513
 * 
 * Defines possible outcomes of audit events:
 * - SUCCESS: Action executed successfully
 * - FAILURE: Action failed
 * - UNKNOWN: Outcome unknown
 */
enum AuditOutcome: string
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';
    case UNKNOWN = 'unknown';

    /**
     * Get a readable description of the outcome
     */
    public function description(): string
    {
        return match($this) {
            self::SUCCESS => 'Successful',
            self::FAILURE => 'Failed',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Check if the outcome is successful
     */
    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if the outcome represents a failure
     */
    public function isFailed(): bool
    {
        return $this === self::FAILURE;
    }
}
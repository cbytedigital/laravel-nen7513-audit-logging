<?php

namespace CbyteDigital\Nen7513AuditLogging\ValueObjects;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome;

/**
 * AuditContext Value Object
 * 
 * Immutable object that contains context information for an audit log entry.
 * Contains all NEN 7513 mandatory fields plus additional detail information.
 */
readonly class AuditContext
{
    public function __construct(
        public ?array $detail = null,
        public AuditOutcome $outcome = AuditOutcome::SUCCESS,
        public string $authProtocol = 'default',
        public bool $authorizationCheck = true,
        public bool $treatmentRelationCheck = false,
        public bool $consentCheck = false,
        public ?string $sourceId = null,
        public ?string $sourceType = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * Create a new context with modified fields
     */
    public function with(
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
        return new self(
            detail: $detail ?? $this->detail,
            outcome: $outcome ?? $this->outcome,
            authProtocol: $authProtocol ?? $this->authProtocol,
            authorizationCheck: $authorizationCheck ?? $this->authorizationCheck,
            treatmentRelationCheck: $treatmentRelationCheck ?? $this->treatmentRelationCheck,
            consentCheck: $consentCheck ?? $this->consentCheck,
            sourceId: $sourceId ?? $this->sourceId,
            sourceType: $sourceType ?? $this->sourceType,
            ipAddress: $ipAddress ?? $this->ipAddress,
            userAgent: $userAgent ?? $this->userAgent,
        );
    }

    /**
     * Create a context for a failed action
     */
    public static function failed(string $reason, array $additionalDetail = []): self
    {
        return new self(
            detail: array_merge(['reason' => $reason], $additionalDetail),
            outcome: AuditOutcome::FAILURE
        );
    }

    /**
     * Create a context for a successful action with details
     */
    public static function successful(array $detail = []): self
    {
        return new self(
            detail: $detail,
            outcome: AuditOutcome::SUCCESS
        );
    }

    /**
     * Create a context for an update action with changed fields
     */
    public static function forUpdate(
        array $changedFields,
        array $oldValues = [],
        array $newValues = []
    ): self {
        $detail = ['changed_fields' => $changedFields];
        
        if (!empty($oldValues)) {
            $detail['old_values'] = $oldValues;
        }
        
        if (!empty($newValues)) {
            $detail['new_values'] = $newValues;
        }
        
        return new self(detail: $detail);
    }

    /**
     * Create a context for emergency access
     */
    public static function emergency(string $reason, string $authorizedBy): self
    {
        return new self(
            detail: [
                'reason' => $reason,
                'authorized_by' => $authorizedBy,
                'emergency_access' => true
            ],
            authProtocol: 'emergency_override',
            treatmentRelationCheck: false // Override normal checks
        );
    }

    /**
     * Create a context with full authorization controls
     */
    public static function withFullAuthorization(
        string $authProtocol = 'oauth2',
        bool $treatmentRelation = true,
        bool $consent = true,
        array $detail = []
    ): self {
        return new self(
            detail: $detail,
            authProtocol: $authProtocol,
            authorizationCheck: true,
            treatmentRelationCheck: $treatmentRelation,
            consentCheck: $consent
        );
    }

    /**
     * Add detail information to existing context
     */
    public function addDetail(string $key, mixed $value): self
    {
        return new self(
            detail: array_merge($this->detail ?? [], [$key => $value]),
            outcome: $this->outcome,
            authProtocol: $this->authProtocol,
            authorizationCheck: $this->authorizationCheck,
            treatmentRelationCheck: $this->treatmentRelationCheck,
            consentCheck: $this->consentCheck,
            sourceId: $this->sourceId,
            sourceType: $this->sourceType,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
        );
    }

    /**
     * Merge additional detail information
     */
    public function mergeDetail(array $additionalDetail): self
    {
        return new self(
            detail: array_merge($this->detail ?? [], $additionalDetail),
            outcome: $this->outcome,
            authProtocol: $this->authProtocol,
            authorizationCheck: $this->authorizationCheck,
            treatmentRelationCheck: $this->treatmentRelationCheck,
            consentCheck: $this->consentCheck,
            sourceId: $this->sourceId,
            sourceType: $this->sourceType,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
        );
    }

    /**
     * Check if the context represents a failure
     */
    public function isFailed(): bool
    {
        return $this->outcome === AuditOutcome::FAILURE;
    }

    /**
     * Check if the context represents success
     */
    public function isSuccessful(): bool
    {
        return $this->outcome === AuditOutcome::SUCCESS;
    }

    /**
     * Check if detail information is present
     */
    public function hasDetail(): bool
    {
        return !empty($this->detail);
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'detail' => $this->detail,
            'outcome' => $this->outcome->value,
            'authorization_protocol' => $this->authProtocol,
            'authorization_check' => $this->authorizationCheck,
            'treatment_relation_check' => $this->treatmentRelationCheck,
            'consent_check' => $this->consentCheck,
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
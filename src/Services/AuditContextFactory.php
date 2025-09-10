<?php

namespace CbyteDigital\Nen7513AuditLogging\Services;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;

/**
 * AuditContextFactory
 * 
 * Centralized factory for creating AuditContext instances.
 * Provides consistent defaults and eliminates duplicate context creation logic.
 */
class AuditContextFactory
{
    /**
     * Create a default successful context
     * 
     * @param array|null $detail Optional detail information
     * @return AuditContext
     */
    public function createDefault(?array $detail = null): AuditContext
    {
        return new AuditContext(
            detail: $detail,
            outcome: AuditOutcome::SUCCESS,
            authProtocol: config('audit-log.authorization.default_protocol', 'default'),
            authorizationCheck: config('audit-log.authorization.default_authorization_check', false),
            treatmentRelationCheck: config('audit-log.authorization.default_treatment_relation_check', false),
            consentCheck: config('audit-log.authorization.default_consent_check', false),
            sourceId: $this->getSourceId(),
            sourceType: $this->getSourceType(),
            ipAddress: $this->getIpAddress(),
            userAgent: $this->getUserAgent()
        );
    }

    /**
     * Create a context for failed actions
     * 
     * @param string|null $reason Failure reason
     * @param array|null $additionalDetail Additional detail information
     * @return AuditContext
     */
    public function createFailure(?string $reason = null, ?array $additionalDetail = null): AuditContext
    {
        $detail = $additionalDetail ?? [];
        if ($reason) {
            $detail['reason'] = $reason;
        }

        return new AuditContext(
            detail: $detail,
            outcome: AuditOutcome::FAILURE,
            authProtocol: config('audit-log.authorization.default_protocol', 'default'),
            authorizationCheck: config('audit-log.authorization.default_authorization_check', false),
            treatmentRelationCheck: config('audit-log.authorization.default_treatment_relation_check', false),
            consentCheck: config('audit-log.authorization.default_consent_check', false),
            sourceId: $this->getSourceId(),
            sourceType: $this->getSourceType(),
            ipAddress: $this->getIpAddress(),
            userAgent: $this->getUserAgent()
        );
    }

    /**
     * Create context for emergency access
     * 
     * @param string $reason Emergency reason
     * @param string $authorizedBy Who authorized the emergency access
     * @param array|null $additionalDetail Additional detail information
     * @return AuditContext
     */
    public function createEmergency(string $reason, string $authorizedBy, ?array $additionalDetail = null): AuditContext
    {
        $detail = array_merge($additionalDetail ?? [], [
            'reason' => $reason,
            'authorized_by' => $authorizedBy,
            'emergency_access' => true
        ]);

        return new AuditContext(
            detail: $detail,
            outcome: AuditOutcome::SUCCESS,
            authProtocol: 'emergency_override',
            authorizationCheck: false,
            treatmentRelationCheck: false,
            consentCheck: false,
            sourceId: $this->getSourceId(),
            sourceType: $this->getSourceType(),
            ipAddress: $this->getIpAddress(),
            userAgent: $this->getUserAgent()
        );
    }

    /**
     * Create context with full authorization controls
     * 
     * @param string $authProtocol Authentication protocol used
     * @param bool $treatmentRelation Whether treatment relation was checked
     * @param bool $consent Whether consent was checked
     * @param array|null $detail Optional detail information
     * @return AuditContext
     */
    public function createWithFullAuthorization(string $authProtocol = 'oauth2', bool $treatmentRelation = true, bool $consent = true, ?array $detail = null): AuditContext
    {
        return new AuditContext(
            detail: $detail,
            outcome: AuditOutcome::SUCCESS,
            authProtocol: $authProtocol,
            authorizationCheck: true,
            treatmentRelationCheck: $treatmentRelation,
            consentCheck: $consent,
            sourceId: $this->getSourceId(),
            sourceType: $this->getSourceType(),
            ipAddress: $this->getIpAddress(),
            userAgent: $this->getUserAgent()
        );
    }

    /**
     * Create context for CREATE operations with full data logging
     * 
     * @param array $createdData The data that was created
     * @param array|null $additionalDetail Additional detail information
     * @return AuditContext
     */
    public function createForCreate(array $createdData, ?array $additionalDetail = null): AuditContext
    {
        $detail = array_merge($additionalDetail ?? [], [
            'operation' => 'create',
            'created_data' => $createdData
        ]);

        return $this->createDefault($detail);
    }

    /**
     * Create context for update operations
     * 
     * @param array $changedFields List of changed field names
     * @param array|null $oldValues Old values (field => value)
     * @param array|null $newValues New values (field => value)
     * @param array|null $additionalDetail Additional detail information
     * @return AuditContext
     */
    public function createForUpdate(array $changedFields, ?array $oldValues = null, ?array $newValues = null, ?array $additionalDetail = null): AuditContext
    {
        $detail = array_merge($additionalDetail ?? [], [
            'changed_fields' => $changedFields
        ]);

        if ($oldValues) {
            $detail['old_values'] = $oldValues;
        }

        if ($newValues) {
            $detail['new_values'] = $newValues;
        }

        return $this->createDefault($detail);
    }

    /**
     * Create context for parent-child audit logging
     * 
     * @param string $childAction Action performed on child (created/updated/deleted)
     * @param string $childType Type of child model
     * @param string|int $childId ID of the child
     * @param string|null $childName Descriptive name of the child
     * @param array|null $changeDetails Details about what changed
     * @param array|null $additionalDetail Additional detail information
     * @return AuditContext
     */
    public function createForParentChild(
        string $childAction,
        string $childType,
        string|int $childId,
        ?string $childName = null,
        ?array $changeDetails = null,
        ?array $additionalDetail = null
    ): AuditContext {
        $detail = array_merge($additionalDetail ?? [], [
            'child_action' => $childAction,
            'child_type' => $childType,
            'child_id' => (string) $childId,
        ]);

        if ($childName) {
            $detail['child_name'] = $childName;
        }

        if ($changeDetails) {
            $detail = array_merge($detail, $changeDetails);
        }

        return $this->createDefault($detail);
    }

    /**
     * Get current source ID (session or request ID)
     * 
     * @return string|null
     */
    private function getSourceId(): ?string
    {
        if (function_exists('session') && session()->getId()) {
            return session()->getId();
        }

        if (function_exists('request') && request()->hasHeader('X-Request-ID')) {
            return request()->header('X-Request-ID');
        }

        return null;
    }

    /**
     * Get source type based on context
     * 
     * @return string|null
     */
    private function getSourceType(): ?string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        if (function_exists('request')) {
            return 'web';
        }

        return null;
    }

    /**
     * Get current IP address
     * 
     * @return string|null
     */
    private function getIpAddress(): ?string
    {
        if (function_exists('request') && request()) {
            return request()->ip();
        }

        return null;
    }

    /**
     * Get current user agent
     * 
     * @return string|null
     */
    private function getUserAgent(): ?string
    {
        if (function_exists('request') && request()) {
            return request()->userAgent();
        }

        return null;
    }
}
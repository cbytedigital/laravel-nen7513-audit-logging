<?php

namespace CbyteDigital\Nen7513AuditLogging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * AuditLog Facade
 * 
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService forActor(\Illuminate\Database\Eloquent\Model $actor)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService withContext(array $detail = null, \CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome $outcome = null, string $authProtocol = null, bool $authorizationCheck = null, bool $treatmentRelationCheck = null, bool $consentCheck = null)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService withDetail(array $detail)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService failed(string $reason = null)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService successful(array $detail = [])
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService emergency(string $reason, string $authorizedBy)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService withFullAuthorization(string $authProtocol = 'oauth2', bool $treatmentRelation = true, bool $consent = true)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService withAuthorization(bool $authorized = true)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService withTreatmentRelation(bool $hasRelation = true)
 * @method static \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService withConsent(bool $hasConsent = true)
 * @method static void created($object, string $name = null)
 * @method static void read($object, string $name = null)
 * @method static void updated($object, array $changedFields = [], string $name = null)
 * @method static void deleted($object, string $name = null)
 * @method static void execute($object, string $name = null)
 * 
 * @see \CbyteDigital\Nen7513AuditLogging\Services\AuditLogService
 */
class AuditLog extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'audit-log-facade';
    }
}
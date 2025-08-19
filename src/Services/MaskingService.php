<?php

namespace CbyteDigital\Nen7513AuditLogging\Services;

/**
 * MaskingService
 * 
 * Service for masking sensitive data in audit logs for display purposes.
 * Implements data privacy protection while maintaining audit trail integrity.
 */
class MaskingService
{
    /**
     * Mask sensitive data in an audit log object for display
     * 
     * @param object $log The audit log object to mask
     * @return object Masked copy of the audit log object
     */
    public function maskAuditLogForDisplay(object $log): object
    {
        if (!config('audit-log.enable_masking', true)) {
            return $log;
        }
        $maskedLog = clone $log;

        if (isset($maskedLog->object_name)) {
            $maskedLog->object_name = $this->maskValue($maskedLog->object_name);
        }

        if (isset($maskedLog->object_detail) && $maskedLog->object_detail) {
            $detail = json_decode($maskedLog->object_detail, true);
            if (is_array($detail)) {
                $maskedDetail = $this->maskObjectDetail($detail);
                $maskedLog->object_detail = json_encode($maskedDetail);
            }
        }

        return $maskedLog;
    }

    /**
     * Mask a collection of audit logs for display
     * 
     * @param iterable $logs Collection of audit log objects to mask
     * @return iterable Masked copy of the audit logs collection
     */
    public function maskAuditLogsForDisplay(iterable $logs): iterable
    {
        if (!config('audit-log.enable_masking', true)) {
            return $logs;
        }

        if (is_array($logs)) {
            return array_map([$this, 'maskAuditLogForDisplay'], $logs);
        }

        if (method_exists($logs, 'getCollection')) {
            $maskedCollection = $logs->getCollection()->map(function ($log) {
                return $this->maskAuditLogForDisplay($log);
            });
            $logs->setCollection($maskedCollection);
            return $logs;
        } elseif (method_exists($logs, 'map')) {
            return $logs->map(function ($log) {
                return $this->maskAuditLogForDisplay($log);
            });
        }

        if (is_object($logs)) {
            return $this->maskAuditLogForDisplay($logs);
        }

        return $logs;
    }

    /**
     * Mask object detail data according to configuration
     * 
     * @param array $detail The detail array to mask
     * @return array Masked detail array
     */
    private function maskObjectDetail(array $detail): array
    {
        $sensitiveFields = config('audit-log.masking.sensitive_fields');
        
        if (!$sensitiveFields || !is_array($sensitiveFields)) {
            return $detail;
        }
        
        return $this->maskArrayRecursive($detail, $sensitiveFields);
    }
    
    /**
     * Recursively mask sensitive data in arrays
     * 
     * @param array $data The data array to mask
     * @param array $sensitiveFields List of sensitive field names
     * @return array Masked data array
     */
    private function maskArrayRecursive(array $data, array $sensitiveFields): array
    {
        $masked = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->maskArrayRecursive($value, $sensitiveFields);
            } elseif (is_string($value) && $this->shouldMaskField($key, $sensitiveFields)) {
                $masked[$key] = $this->maskValue($value);
            } else {
                $masked[$key] = $value;
            }
        }
        
        return $masked;
    }
    
    /**
     * Check if a field should be masked
     * 
     * @param string $fieldName The field name to check
     * @param array $sensitiveFields List of sensitive field names
     * @return bool True if the field should be masked
     */
    private function shouldMaskField(string $fieldName, array $sensitiveFields): bool
    {
        if (in_array($fieldName, $sensitiveFields)) {
            return true;
        }
        
        foreach ($sensitiveFields as $sensitive) {
            if (str_contains($fieldName, $sensitive)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mask sensitive values according to configuration
     * 
     * @param string|null $value The value to mask
     * @return string|null The masked value
     */
    private function maskValue(?string $value): ?string
    {
        if (!$value) {
            return $value;
        }
        
        return $this->mask($value);
    }

    /**
     * Mask any value by keeping only first character of each word/number
     * Examples:
     * - "Jan Jansen" -> "J** J*****"
     * - "Jan van der Berg" -> "J** v** d** B***"
     * - "Patient123" -> "P*******"
     * - "12345" -> "1****"
     * - "René" -> "R***"
     * - "Müller" -> "M*****"
     * 
     * @param string $value The value to mask
     * @return string The masked value
     */
    private function mask(string $value): string
    {
        return preg_replace_callback('/\b(\w)(\w*)\b/u', function($matches) {
            return $matches[1] . str_repeat('*', mb_strlen($matches[2], 'UTF-8'));
        }, $value);
    }
}

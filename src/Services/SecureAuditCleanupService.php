<?php

namespace CbyteDigital\Nen7513AuditLogging\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecureAuditCleanupService
{
    /**
     * Securely delete audit logs for retention policy
     * This temporarily disables triggers for authorized cleanup only
     */
    public function deleteOldLogs(Carbon $cutoffDate, int $batchSize = 1000): int
    {
        // Critical security verification
        $this->verifyAuthorizedCleanupContext();
        
        $deleted = 0;
        $tableName = config('audit-log.table_name', 'audit_logs');
        
        // Only proceed for MySQL with trigger support
        if (DB::getDriverName() !== 'mysql') {
            return $this->deleteWithoutTriggers($tableName, $cutoffDate, $batchSize);
        }
        
        try {
            // Temporarily disable ONLY the deletion trigger for authorized cleanup
            $this->temporarilyDisableDeletionTrigger();
            
            // Perform the actual cleanup in batches
            while (true) {
                $batchDeleted = DB::table($tableName)
                    ->where('event_time', '<', $cutoffDate)
                    ->limit($batchSize)
                    ->delete();

                if ($batchDeleted === 0) {
                    break;
                }

                $deleted += $batchDeleted;
                
                // Small pause to prevent database overload
                usleep(100000); // 100ms
            }
            
        } finally {
            // CRITICAL: Always restore the deletion trigger
            $this->restoreDeletionTrigger();
        }
        
        // Log this security-critical operation
        $this->logSecureCleanup($deleted, $cutoffDate);
        
        return $deleted;
    }
    
    /**
     * Verify this is being called from the authorized cleanup command
     * Multiple security layers to prevent unauthorized access
     */
    private function verifyAuthorizedCleanupContext(): void
    {
        // 1. Must be running in console
        if (!app()->runningInConsole()) {
            throw new \RuntimeException(
                'SECURITY VIOLATION: Audit cleanup can only be performed from console commands'
            );
        }
        
        // 2. Check call stack for authorized caller
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $authorizedCaller = false;
        
        foreach ($backtrace as $frame) {
            if (isset($frame['class']) && 
                $frame['class'] === 'CbyteDigital\\Nen7513AuditLogging\\Commands\\CleanupOldLogsCommand') {
                $authorizedCaller = true;
                break;
            }
        }
        
        if (!$authorizedCaller) {
            throw new \RuntimeException(
                'SECURITY VIOLATION: SecureAuditCleanupService can only be called from CleanupOldLogsCommand'
            );
        }
        
        // 3. Additional check: verify we're in the expected execution context
        $commandLine = $_SERVER['argv'] ?? [];
        if (!in_array('audit:cleanup', $commandLine)) {
            throw new \RuntimeException(
                'SECURITY VIOLATION: Unexpected execution context for audit cleanup'
            );
        }
    }
    
    /**
     * Temporarily disable the deletion trigger for authorized cleanup
     */
    private function temporarilyDisableDeletionTrigger(): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_audit_log_deletes');
            Log::warning('SECURITY: Temporarily disabled audit deletion trigger for authorized retention cleanup');
        } catch (\Exception $e) {
            Log::critical('SECURITY FAILURE: Cannot disable audit deletion trigger: ' . $e->getMessage());
            throw new \RuntimeException('Cannot proceed with cleanup - trigger management failed');
        }
    }
    
    /**
     * Restore the deletion trigger after cleanup
     * This is CRITICAL for maintaining security
     */
    private function restoreDeletionTrigger(): void
    {
        try {
            DB::unprepared('
                CREATE TRIGGER prevent_audit_log_deletes
                BEFORE DELETE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE "45000" 
                    SET MESSAGE_TEXT = "NEN 7513 VIOLATION: Direct deletion forbidden - use audit:cleanup command";
                END
            ');
            Log::info('SECURITY: Restored audit deletion trigger after authorized cleanup');
        } catch (\Exception $e) {
            Log::critical('CRITICAL SECURITY FAILURE: Cannot restore audit deletion trigger: ' . $e->getMessage());
            // This is a critical security breach - the system must alert administrators
            throw new \RuntimeException('CRITICAL: Cannot restore audit log protection - immediate admin intervention required');
        }
    }
    
    /**
     * Delete logs for non-MySQL databases (no trigger support)
     */
    private function deleteWithoutTriggers(string $tableName, Carbon $cutoffDate, int $batchSize): int
    {
        $deleted = 0;
        
        while (true) {
            $batchDeleted = DB::table($tableName)
                ->where('event_time', '<', $cutoffDate)
                ->limit($batchSize)
                ->delete();

            if ($batchDeleted === 0) {
                break;
            }

            $deleted += $batchDeleted;
            usleep(100000); // 100ms
        }
        
        return $deleted;
    }
    
    /**
     * Log the secure cleanup operation for security audit trail
     */
    private function logSecureCleanup(int $deletedCount, Carbon $cutoffDate): void
    {
        Log::warning('SECURITY: Secure audit cleanup completed', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'authorized_by' => 'CleanupOldLogsCommand',
            'trigger_management' => 'temporary_disable_restore',
            'security_verification' => 'multi_layer_passed',
            'user' => posix_getpwuid(posix_geteuid())['name'] ?? 'unknown',
            'pid' => getmypid(),
        ]);
    }
}
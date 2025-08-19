<?php

namespace CbyteDigital\Nen7513AuditLogging\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CleanupOldLogsCommand
 * 
 * Command for cleaning up old audit logs according to NEN 7513 retention policy.
 * NEN 7513 requires minimum 2 years, maximum 15 years retention.
 */
class CleanupOldLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:cleanup 
                            {--days= : Override default retention days (must respect min/max limits)}
                            {--batch-size= : Number of records to delete per batch}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup old audit logs according to NEN 7513 retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Audit Log Cleanup - NEN 7513 Compliant');
        $this->line('================================================');

        // Get configuration
        $config = $this->getRetentionConfig();
        
        // Validate retention configuration
        if (!$this->validateRetentionConfig($config)) {
            return Command::FAILURE;
        }

        // Display configuration
        $this->displayConfiguration($config);

        // Dry run check
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        // Confirmation (unless --force)
        if (!$isDryRun && !$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete old audit logs?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Perform cleanup
        return $this->performCleanup($config, $isDryRun);
    }

    /**
     * Get retention configuration
     */
    private function getRetentionConfig(): array
    {
        $defaultDays = config('audit-log.retention.default_days', 730);
        $minDays = config('audit-log.retention.min_days', 730);
        $maxDays = config('audit-log.retention.max_days', 5475);
        $batchSize = config('audit-log.retention.batch_size', 1000);
        $tableName = config('audit-log.table_name', 'audit_logs');

        // Override via command options
        $retentionDays = $this->option('days') 
            ? (int) $this->option('days') 
            : $defaultDays;

        $batchSize = $this->option('batch-size') 
            ? (int) $this->option('batch-size') 
            : $batchSize;

        return [
            'retention_days' => $retentionDays,
            'min_days' => $minDays,
            'max_days' => $maxDays,
            'batch_size' => $batchSize,
            'table_name' => $tableName,
        ];
    }

    /**
     * Validate retention configuration against NEN 7513
     */
    private function validateRetentionConfig(array $config): bool
    {
        $retentionDays = $config['retention_days'];
        $minDays = $config['min_days'];
        $maxDays = $config['max_days'];

        // Check minimum retention period (NEN 7513)
        if ($retentionDays < $minDays) {
            $this->error("Retention period ({$retentionDays} days) is below minimum required by NEN 7513 ({$minDays} days)");
            $this->line("Audit logs must be retained for at least 2 years (730 days)");
            return false;
        }

        // Check maximum retention period (NEN 7513)
        if ($retentionDays > $maxDays) {
            $this->error("Retention period ({$retentionDays} days) exceeds maximum allowed by NEN 7513 ({$maxDays} days)");
            $this->line("Audit logs must not be retained longer than 15 years (5475 days)");
            return false;
        }

        return true;
    }

    /**
     * Display current configuration
     */
    private function displayConfiguration(array $config): void
    {
        $this->line('Configuration:');
        $this->line("  Table: {$config['table_name']}");
        $this->line("  Retention Period: {$config['retention_days']} days (" . round($config['retention_days'] / 365, 1) . " years)");
        $this->line("  Batch Size: {$config['batch_size']} records");
        $this->line("  NEN 7513 Limits: {$config['min_days']}-{$config['max_days']} days (2-15 years)");
        
        $cutoffDate = Carbon::now()->subDays($config['retention_days']);
        $this->line("  Cutoff Date: {$cutoffDate->toDateTimeString()}");
        $this->line('');
    }

    /**
     * Perform the cleanup
     */
    private function performCleanup(array $config, bool $isDryRun): int
    {
        $tableName = $config['table_name'];
        $retentionDays = $config['retention_days'];
        $batchSize = $config['batch_size'];
        
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        // Count total number of records to be deleted
        $totalToDelete = DB::table($tableName)
            ->where('event_time', '<', $cutoffDate)
            ->count();

        if ($totalToDelete === 0) {
            $this->info('No old audit logs found to cleanup.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalToDelete} audit logs older than {$retentionDays} days");

        if ($isDryRun) {
            $this->showDryRunStats($tableName, $cutoffDate);
            return Command::SUCCESS;
        }

        // Perform cleanup in batches
        return $this->deleteInBatches($tableName, $cutoffDate, $batchSize, $totalToDelete);
    }

    /**
     * Show statistics for dry run
     */
    private function showDryRunStats(string $tableName, Carbon $cutoffDate): void
    {
        $this->line('DRY RUN - Records that would be deleted:');
        
        // Statistics per action type
        $actionStats = DB::table($tableName)
            ->select('action', DB::raw('COUNT(*) as count'))
            ->where('event_time', '<', $cutoffDate)
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();

        $this->table(['Action', 'Count'], $actionStats->map(function ($stat) {
            return [$stat->action, $stat->count];
        })->toArray());

        // Statistics per month
        $monthlyStats = DB::table($tableName)
            ->selectRaw('YEAR(event_time) as year, MONTH(event_time) as month, COUNT(*) as count')
            ->where('event_time', '<', $cutoffDate)
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        if ($monthlyStats->isNotEmpty()) {
            $this->line('');
            $this->line('Monthly breakdown (last 12 months):');
            $this->table(['Year-Month', 'Count'], $monthlyStats->map(function ($stat) {
                return [sprintf('%04d-%02d', $stat->year, $stat->month), $stat->count];
            })->toArray());
        }
    }

    /**
     * Delete records in batches using secure cleanup service
     * Uses temporary trigger management for maximum security (NEN 7513 compliance)
     */
    private function deleteInBatches(string $tableName, Carbon $cutoffDate, int $batchSize, int $totalToDelete): int
    {
        $progressBar = $this->output->createProgressBar($totalToDelete);
        $progressBar->start();

        try {
            // Use the secure cleanup service that handles trigger management
            $secureCleanup = app(\CbyteDigital\Nen7513AuditLogging\Services\SecureAuditCleanupService::class);
            
            // This service will:
            // 1. Verify we're calling from authorized context (this command)
            // 2. Verify console-only execution 
            // 3. Temporarily disable deletion trigger
            // 4. Perform cleanup in batches
            // 5. Restore deletion trigger (CRITICAL)
            // 6. Log the security-sensitive operation
            $deleted = $secureCleanup->deleteOldLogs($cutoffDate, $batchSize);
            
            // Update progress bar to show completion
            $progressBar->advance($deleted);
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->error("Secure cleanup failed: " . $e->getMessage());
            $this->error("Audit log protection remains intact.");
            return 0;
        }

        $progressBar->finish();
        $this->line('');
        $this->info("Successfully deleted {$deleted} old audit logs");

        // Log this cleanup action in the audit system itself
        $this->logCleanupAction($deleted, $cutoffDate);

        return $deleted;
    }

    /**
     * Log the cleanup action itself as audit event
     */
    private function logCleanupAction(int $deletedCount, Carbon $cutoffDate): void
    {
        try {
            // Use system actor for this logging
            $systemActor = app('audit.system_actor');
            
            if ($systemActor) {
                $auditLogger = app(\CbyteDigital\Nen7513AuditLogging\Services\AuditLogger::class);
                
                $context = \CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext::successful([
                    'operation' => 'cleanup_old_logs',
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                    'command' => 'audit:cleanup',
                    'executed_at' => now()->toDateTimeString(),
                ]);

                $auditLogger->log(
                    \CbyteDigital\Nen7513AuditLogging\Enums\AuditAction::EXECUTE,
                    $systemActor,
                    'audit_logs',
                    'cleanup',
                    "Deleted {$deletedCount} old audit logs",
                    $context
                );
            }
        } catch (\Exception $e) {
            // Don't fail if the audit logging of the cleanup itself fails
            $this->warn("Could not log cleanup action: {$e->getMessage()}");
        }
    }
}
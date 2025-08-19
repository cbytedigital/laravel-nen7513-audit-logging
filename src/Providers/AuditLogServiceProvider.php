<?php

namespace CbyteDigital\Nen7513AuditLogging\Providers;

use CbyteDigital\Nen7513AuditLogging\Actors\GuestActor;
use CbyteDigital\Nen7513AuditLogging\Actors\SystemActor;
use CbyteDigital\Nen7513AuditLogging\Commands\CleanupOldLogsCommand;
use CbyteDigital\Nen7513AuditLogging\Services\ActorResolver;
use CbyteDigital\Nen7513AuditLogging\Services\AuditContextFactory;
use CbyteDigital\Nen7513AuditLogging\Services\AuditLogger;
use CbyteDigital\Nen7513AuditLogging\Services\AuditLogQuery;
use CbyteDigital\Nen7513AuditLogging\Services\AuditLogService;
use CbyteDigital\Nen7513AuditLogging\Services\MaskingService;
use Illuminate\Support\ServiceProvider;

/**
 * AuditLogServiceProvider
 * 
 * Registers all services and configuration for the audit log package
 */
class AuditLogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap package services
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'audit-log');
        $this->bootPublishing();
        $this->bootCommands();
        $this->bootScheduler();
    }

    /**
     * Register package services
     */
    public function register(): void
    {
        $this->registerConfiguration();
        $this->registerServices();
        $this->registerActors();
    }

    /**
     * Bootstrap publishing of configuration and migrations
     */
    private function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config file
            $this->publishes([
                __DIR__.'/../../config/audit-log.php' => config_path('audit-log.php'),
            ], 'audit-log-config');

            // Publish migration with dynamic timestamp
            $this->publishes([
                __DIR__.'/../../database/migrations/2024_01_01_000000_create_audit_logs_table.php' => 
                $this->getMigrationFileName(),
            ], 'audit-log-migrations');

            // Publish views
            $this->publishes([
                __DIR__.'/../../resources/views/audit-log' => resource_path('views/vendor/audit-log'),
            ], 'audit-log-views');

            // Publish assets
            $this->publishes([
                __DIR__.'/../../public' => public_path('vendor/audit-log'),
            ], 'audit-log-assets');

            // Publish controllers
            $this->publishes([
                __DIR__.'/../../src/Http/Controllers/AuditLogOverviewController.php' => app_path('Http/Controllers/AuditLog/AuditLogOverviewController.php'),
            ], 'audit-log-controllers');

            // Publish everything together
            $this->publishes([
                __DIR__.'/../../config/audit-log.php' => config_path('audit-log.php'),
                __DIR__.'/../../database/migrations/2024_01_01_000000_create_audit_logs_table.php' => 
                $this->getMigrationFileName(),
                __DIR__.'/../../resources/views/audit-log' => resource_path('views/vendor/audit-log'),
                __DIR__.'/../../public' => public_path('vendor/audit-log'),
                __DIR__.'/../../src/Http/Controllers/AuditLogOverviewController.php' => app_path('Http/Controllers/AuditLog/AuditLogOverviewController.php'),

            ], 'audit-log');
        }
    }

    /**
     * Bootstrap commands
     */
    private function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupOldLogsCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap scheduler integration for automatic cleanup
     */
    private function bootScheduler(): void
    {
        if ($this->app->runningInConsole()) {
            // Register the scheduled task after the application has booted
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                
                // Get cron expression from config (default: daily at 2:00 AM)
                $cronExpression = config('audit-log.retention.cleanup_schedule', '0 2 * * *');
                
                // Schedule the cleanup command
                $schedule->command('audit:cleanup --force')
                    ->cron($cronExpression)
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->name('audit-log-cleanup')
                    ->description('Cleanup old audit logs according to NEN 7513 retention policy');
            });
        }
    }

    /**
     * Register configuration
     */
    private function registerConfiguration(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/audit-log.php',
            'audit-log'
        );
    }

    /**
     * Get migration filename with current timestamp
     * This ensures the migration runs in the correct order when published
     */
    private function getMigrationFileName(): string
    {
        // Check if migration already exists to avoid duplicates
        $existingMigrations = glob(database_path('migrations/*_' . 'create_audit_logs_table.php'));
        if (!empty($existingMigrations)) {
            return $existingMigrations[0]; // Return existing migration path
        }
        
        // Generate new timestamp in Laravel migration format
        $timestamp = now()->format('Y_m_d_His');
        return database_path("migrations/{$timestamp}_create_audit_logs_table.php");
    }

    /**
     * Register package services
     */
    private function registerServices(): void
    {
        // Register ActorResolver as singleton
        $this->app->singleton(ActorResolver::class, function ($app) {
            return new ActorResolver($app);
        });

        // Register AuditContextFactory as singleton
        $this->app->singleton(AuditContextFactory::class, function ($app) {
            return new AuditContextFactory();
        });

        // Register AuditLogger as singleton
        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger($app->make(ActorResolver::class));
        });

        // Register AuditLogService (fluent interface)
        $this->app->singleton('audit-log-facade', function ($app) {
            return new AuditLogService(
                $app->make(AuditLogger::class),
                $app->make(ActorResolver::class),
                $app->make(AuditContextFactory::class)
            );
        });

        // Register AuditLogQuery
        $this->app->bind(AuditLogQuery::class, function ($app) {
            return new AuditLogQuery();
        });

        // Register MaskingService for display masking
        $this->app->singleton(MaskingService::class, function ($app) {
            return new MaskingService();
        });

        // Register SecureAuditCleanupService for authorized retention cleanup
        $this->app->singleton(\CbyteDigital\Nen7513AuditLogging\Services\SecureAuditCleanupService::class, function ($app) {
            return new \CbyteDigital\Nen7513AuditLogging\Services\SecureAuditCleanupService();
        });

        // Register AuditLogOverviewController
        $this->app->bind(\CbyteDigital\Nen7513AuditLogging\Http\Controllers\AuditLogOverviewController::class, function ($app) {
            return new \CbyteDigital\Nen7513AuditLogging\Http\Controllers\AuditLogOverviewController(
                $app->make(MaskingService::class)
            );
        });

        // Helper alias
        $this->app->alias('audit-log-facade', AuditLogService::class);
    }

    /**
     * Register audit actors
     */
    private function registerActors(): void
    {
        $this->registerSystemActor();
        $this->registerGuestActor();
    }

    /**
     * Register system actor for console commands and automated processes
     */
    private function registerSystemActor(): void
    {
        $this->app->singleton('audit.system_actor', function ($app) {
            return new SystemActor();
        });
    }

    /**
     * Register guest actor for unauthenticated website visitors
     */
    private function registerGuestActor(): void
    {
        $this->app->singleton('audit.guest_actor', function ($app) {
            return new GuestActor();
        });
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            ActorResolver::class,
            AuditContextFactory::class,
            AuditLogger::class,
            AuditLogService::class,
            'audit-log-facade',
            AuditLogQuery::class,
            MaskingService::class,
            'audit.system_actor',
            'audit.guest_actor',
        ];
    }
}
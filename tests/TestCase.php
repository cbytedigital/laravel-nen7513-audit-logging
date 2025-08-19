<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests;

use CbyteDigital\Nen7513AuditLogging\Providers\AuditLogServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CbyteDigital\\Nen7513AuditLogging\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AuditLogServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'AuditLog' => \CbyteDigital\Nen7513AuditLogging\Facades\AuditLog::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/2024_01_01_000000_create_audit_logs_table.php';
        $migration->up();
    }
}
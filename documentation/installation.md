# Installation Guide

## Requirements

- PHP ^8.2
- Laravel ^11.0|^12.0

## Step 1: Install Package

```bash
composer require cbytedigital/nen7513-audit-logging
```

## Step 2: Publish & Migrate

```bash
# Publish config and migration
php artisan vendor:publish --tag=audit-log

# Run migration
php artisan migrate
```

## Step 3: Add Trait to Models

```php
<?php

use CbyteDigital\Nen7513AuditLogging\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use Auditable;
    
    // Required: How this model appears in logs
    public function getAuditName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

## Step 4: Optional User Setup

Make your User model audit-aware:

```php
<?php

use CbyteDigital\Nen7513AuditLogging\Traits\AuditActor;

class User extends Authenticatable
{
    use AuditActor;
    
    public function getAuditActorName(): string
    {
        return $this->name;
    }
    
    public function getAuditActorRole(): string
    {
        return $this->role ?? 'user';
    }
}
```

## Optional: Web Dashboard

For the web interface:

```bash
# Publish views and assets
php artisan vendor:publish --tag=audit-log-views
php artisan vendor:publish --tag=audit-log-assets
```

Add routes:

```php
// routes/web.php
use CbyteDigital\Nen7513AuditLogging\Http\Controllers\AuditLogOverviewController;

Route::middleware(['auth'])->prefix('audit')->group(function () {
    Route::get('/', [AuditLogOverviewController::class, 'index']);
    Route::get('/export', [AuditLogOverviewController::class, 'export']);
});
```

## Verification

Test that logging works:

```php
$patient = Patient::create(['name' => 'Test Patient']);

// Check logs
DB::table('audit_logs')->where('object_type', 'patients')->get();
```

## Next Steps

- [Configure audit behavior](configuration.md)
- [Learn automatic logging](automatic-logging.md)
- [Explore manual logging](manual-logging.md)
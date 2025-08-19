# Web Dashboard

The package includes a web dashboard for viewing and exporting audit logs.

## Setup

### 1. Publish Assets

```bash
php artisan vendor:publish --tag=audit-log-views
php artisan vendor:publish --tag=audit-log-assets
```

### 2. Add Routes

```php
// routes/web.php
use CbyteDigital\Nen7513AuditLogging\Http\Controllers\AuditLogOverviewController;

Route::middleware(['auth'])->prefix('audit')->group(function () {
    Route::get('/', [AuditLogOverviewController::class, 'index'])
        ->name('audit-logs.index');
    Route::get('/export', [AuditLogOverviewController::class, 'export'])
        ->name('audit-logs.export');
});
```

### 3. Protect Access

```php
// Add authorization
Route::middleware(['auth', 'can:view-audit-logs'])->prefix('audit')->group(function () {
    // routes...
});

// In AuthServiceProvider
Gate::define('view-audit-logs', function ($user) {
    return in_array($user->role, ['admin', 'auditor']);
});
```

## Features

### Audit Log Table
- Paginated view of all audit logs
- Sortable by timestamp, action, actor, object
- Expandable rows showing detailed information

### Filtering Options
- **Date range** - Filter by start/end dates
- **Action type** - Filter by create, read, update, delete, execute
- **Actor** - Filter by specific users
- **Object** - Filter by specific objects or types
- **Outcome** - Filter by success/failure

### Export Functionality
- **CSV format** - For spreadsheet analysis
- **Excel format** - With formatting
- **JSON format** - Raw data export
- Export respects current filters

### Search
- Global search across all fields
- Search in object names, actor names, details

## Configuration

Customize the dashboard in `config/audit-log.php`:

```php
'ui' => [
    'title' => 'Healthcare Audit Logs',
    'logo' => [
        'type' => 'file', // or 'url'
        'path' => '/images/logo.png',
        'alt' => 'Logo',
    ],
    'expanded_row_color' => '#e0f2fe', // Light blue
],
```

### Environment Variables

```env
AUDIT_LOG_TITLE="Hospital Audit Logs"
AUDIT_LOG_LOGO_TYPE=url
AUDIT_LOG_LOGO_PATH=https://example.com/logo.png
AUDIT_LOG_EXPANDED_COLOR=#f0fdf4
```

## Customization

### Custom Controller

```php
<?php

namespace App\Http\Controllers;

use CbyteDigital\Nen7513AuditLogging\Http\Controllers\AuditLogOverviewController as BaseController;

class CustomAuditController extends BaseController
{
    public function index(Request $request)
    {
        // Add custom logic
        $response = parent::index($request);
        $response->with('customData', $this->getCustomData());
        
        return $response;
    }
}
```

### Custom Views

After publishing views, modify them in:
- `resources/views/vendor/audit-log/index.blade.php`
- `resources/views/vendor/audit-log/partials/`

### Custom Styling

After publishing assets, modify:
- `public/vendor/audit-log/css/audit-logs.css`

```css
/* Custom header */
.audit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Custom table */
.audit-table th {
    background-color: #f8fafc;
}
```

## Security

### Access Control
```php
// Multiple authorization levels
Gate::define('view-audit-logs', function ($user) {
    return $user->hasRole(['admin', 'auditor', 'security']);
});

Gate::define('export-audit-logs', function ($user) {
    return $user->hasRole(['admin', 'auditor']);
});
```

## Performance

### Pagination
The dashboard uses pagination (25 records per page by default) for performance.

### Export Limits
Large exports are processed in chunks to avoid memory issues.

## Troubleshooting

### Dashboard Not Loading
1. Check routes are registered
2. Verify assets are published
3. Check user authorization

### Slow Performance
1. Add database indexes
2. Limit date ranges
3. Consider cleanup of old logs

### Export Errors
1. Check memory limits for large exports
2. Verify file permissions
3. Check export authorization

## Next Steps

- [Configure authorization](configuration.md#security-configuration)
- [Set up monitoring](troubleshooting.md)
- [Learn about NEN 7513 compliance](nen7513-compliance.md)
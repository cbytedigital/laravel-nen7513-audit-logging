# Laravel NEN 7513 Audit Logging

[![PHP from Packagist](https://img.shields.io/packagist/php-v/cbytedigital/nen7513-audit-logging.svg)](https://packagist.org/packages/cbytedigital/nen7513-audit-logging)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cbytedigital/nen7513-audit-logging.svg)](https://packagist.org/packages/cbytedigital/nen7513-audit-logging)
[![Software License](https://img.shields.io/packagist/l/cbytedigital/nen7513-audit-logging.svg)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/cbytedigital/nen7513-audit-logging.svg)](https://packagist.org/packages/cbytedigital/nen7513-audit-logging)

A comprehensive **NEN 7513-compliant** Laravel package for audit logging, designed specifically for healthcare applications and other environments requiring detailed activity tracking and compliance with Dutch healthcare standards.

This package automatically tracks and logs all user interactions with your Laravel application's data, capturing who, what, when, where, why, and how for complete audit traceability.

## Key Features

- **NEN 7513 Compliant** - Meets Dutch healthcare logging standards
- **Automatic Logging** - Zero-configuration CRUD operation tracking
- **Flexible API** - Multiple ways to log: automatic, facade, or trait methods
- **Rich CREATE Logging** - Configurable full model data logging on creation
- **Parent-Child Logging** - Child model changes automatically log on parent with full context
- **Data Privacy** - Automatic masking of sensitive fields
- **Smart Actor Detection** - Automatically identifies users, guests, or system processes
- **Retention Management** - Configurable cleanup with NEN 7513 compliance
- **Advanced Querying** - Powerful filtering and search capabilities
- **Web Dashboard** - Beautiful, responsive audit log viewer with export functionality

## Installation

Use composer to install this package:

```bash
composer require cbytedigital/nen7513-audit-logging
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=audit-log
php artisan migrate
```

## Quick Start

Add the `Auditable` trait to any model you want to track:

```php
<?php

use CbyteDigital\Nen7513AuditLogging\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use Auditable;
    
    public function getAuditName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

That's it! All CRUD operations are now automatically logged:

```php
$patient = Patient::create(['name' => 'John Doe']);     // Logged
$patient->update(['name' => 'John Smith']);             // Logged
$patient->delete();                                     // Logged
```

## Usage Examples

### Automatic Logging
```php
// All operations automatically logged with the Auditable trait
$patient = Patient::find(1);
$patient->update(['status' => 'discharged']);
```

### Manual Logging with Facade
```php
use CbyteDigital\Nen7513AuditLogging\Facades\AuditLog;

AuditLog::read($patient, 'Patient medical record accessed');
AuditLog::failed('Unauthorized access attempt')
    ->forActor($user)
    ->read($patient);
```

### Parent-Child Logging
```php
// Child model changes automatically logged on parent
class ClientHistory extends Model 
{
    use ParentAuditable;
    
    protected static function getParentAuditConfig(): array
    {
        return [
            'parent_relation' => 'client',
            'actions' => ['create', 'update', 'delete'],
        ];
    }
}
```

## Documentation

- **[Installation Guide](documentation/installation.md)** - Complete setup instructions
- **[Automatic Logging](documentation/automatic-logging.md)** - Using the Auditable trait
- **[Manual Logging](documentation/manual-logging.md)** - Using the AuditLog facade
- **[Parent-Child Logging](documentation/parent-child-logging.md)** - Advanced relationship logging
- **[Configuration](documentation/configuration.md)** - All configuration options
- **[Web Dashboard](documentation/web-dashboard.md)** - Setting up the audit log viewer
- **[NEN 7513 Compliance](documentation/nen7513-compliance.md)** - Healthcare standards compliance
- **[API Reference](documentation/api-reference.md)** - Complete method documentation


## Support

| Version | Laravel Version | PHP Version |
|---------|----------------|-------------|
| 1.x     | ^11.0\|^12.0   | ^8.2        |

## Testing

Run the tests with:

```bash
composer test
# or
php vendor/bin/phpunit
```

## Reviewware

This package is completely free to use. If it makes it to your production environment, we'd love it if you left us a Google review! ⭐

You can find us here: [Google review CBYTE](https://g.page/r/CYy5Ay3iwGdZEAE/review)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
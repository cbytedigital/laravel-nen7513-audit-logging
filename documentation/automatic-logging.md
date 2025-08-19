# Automatic Logging

The `Auditable` trait provides zero-configuration automatic logging of CRUD operations.

## Basic Setup

Add the trait to your model:

```php
<?php

use CbyteDigital\Nen7513AuditLogging\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use Auditable;
    
    // Required: How this appears in logs
    public function getAuditName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

## What Gets Logged

These operations are automatically logged:

```php
$patient = Patient::create(['name' => 'John']);     // ✅ CREATE
$patient->update(['name' => 'Jane']);               // ✅ UPDATE  
$patient->delete();                                 // ✅ DELETE
$patient = Patient::find(1);                       // ⚠️ READ (if enabled)
```

## Configuration Methods

### Required: `getAuditName()`

```php
public function getAuditName(): string
{
    return $this->first_name . ' ' . $this->last_name;
}
```

### Optional: Control Which Actions

```php
protected static function getAuditableActions(): array
{
    return [
        'read' => true,     // Enable READ logging
        'create' => true,   
        'update' => true,   
        'delete' => true,   
    ];
}
```

### Optional: Control CREATE Data Logging

```php
protected function shouldLogCreateData(): bool
{
    return false; // Don't log full data on CREATE
}
```

### Optional: Customize CREATE Data

```php
protected function getAuditCreateData(): array
{
    $data = parent::getAuditCreateData();
    
    // Add computed fields
    $data['full_name'] = $this->first_name . ' ' . $this->last_name;
    
    // Remove sensitive fields
    unset($data['password']);
    
    return $data;
}
```

## Disable Auditing Temporarily

```php
$patient->withoutAuditing(function () use ($patient) {
    $patient->update(['internal_field' => 'value']);
});
```

## Manual Logging Methods

The trait also provides manual methods:

```php
$patient->auditRead();
$patient->auditCreate();
$patient->auditUpdate();
$patient->auditDelete();
$patient->auditExecute('custom_action');
```

## Examples

### High-Security Model
```php
class MedicalRecord extends Model
{
    use Auditable;
    
    public function getAuditName(): string
    {
        return "Medical Record #{$this->id}";
    }
    
    protected static function getAuditableActions(): array
    {
        return [
            'read' => true,     // Log all access
            'create' => true,
            'update' => true,
            'delete' => true,
        ];
    }
}
```

### Minimal Logging Model
```php
class SystemLog extends Model
{
    use Auditable;
    
    public function getAuditName(): string
    {
        return "Log #{$this->id}";
    }
    
    protected static function getAuditableActions(): array
    {
        return [
            'read' => false,    // Don't log reads
            'create' => false,  // Don't log creates
            'update' => true,   // Only updates
            'delete' => true,   // and deletes
        ];
    }
    
    protected function shouldLogCreateData(): bool
    {
        return false;
    }
}
```

## Performance Tips

- READ logging is disabled by default
- Use `withoutAuditing()` for bulk operations
- Customize `getAuditCreateData()` to reduce data volume

## Next Steps

- [Configure global settings](configuration.md)
- [Learn manual logging](manual-logging.md)
- [Set up parent-child logging](parent-child-logging.md)
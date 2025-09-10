# Configuration

Configuration options for the NEN 7513 Audit Logging package.

## Configuration File

Publish the configuration file:

```bash
php artisan vendor:publish --tag=audit-log-config
```

This creates `config/audit-log.php`.

## Database Configuration

```php
'table_name' => 'audit_logs',
```

## Retention Policy (NEN 7513)

```php
'retention' => [
    'min_days' => 730,          // 2 years minimum (NEN 7513)
    'max_days' => 5475,         // 15 years maximum (NEN 7513)
    'default_days' => 730,      // Default retention period
    'cleanup_schedule' => '0 2 * * *', // Daily at 2:00 AM
    'batch_size' => 1000,       // Records per batch during cleanup
],
```

## Default Audit Behavior

```php
'default_audit' => [
    'read' => false,   // Disabled to prevent spam
    'create' => true,  // Log creation
    'update' => true,  // Log updates
    'delete' => true,  // Log deletions
    
    'log_create_data' => true,    // Log full model data on CREATE
],
```

### Per-Environment Settings

```php
'default_audit' => [
    'read' => env('AUDIT_LOG_READS', false),
    'create' => env('AUDIT_LOG_CREATES', true),
    'update' => env('AUDIT_LOG_UPDATES', true),
    'delete' => env('AUDIT_LOG_DELETES', true),
    'log_create_data' => env('AUDIT_LOG_CREATE_DATA', true),
],
```

Then in `.env`:

```env
# Development
AUDIT_LOG_READS=false
AUDIT_LOG_CREATE_DATA=false

# Production  
AUDIT_LOG_READS=true
AUDIT_LOG_CREATE_DATA=true
```

## Privacy Protection

### Enable/Disable Masking

```php
'enable_masking' => true,
```

### Sensitive Fields

```php
'masking' => [
    'sensitive_fields' => [
        // Personal information
        'first_name', 'last_name', 'name', 'full_name',
        'email', 'phone', 'mobile',
        
        // Address information
        'address', 'street', 'city', 'postal_code',
        
        // Sensitive identifiers
        'ssn', 'bsn', 'patient_number',
        
        // Dates
        'birth_date', 'date_of_birth', 'dob',
    ],
],
```

### How Masking Works

The package keeps the first character of each word and replaces the rest with asterisks:

```php
// Examples:
"Jan Jansen" -> "J** J*****"
"jan@example.com" -> "j**@e******.c**"
"06-12345678" -> "0*-********"
```

## Source Configuration

```php
'source' => [
    'default_id' => env('APP_NAME', 'laravel_app'),
    'default_type' => 'laravel_application',
],
```

## Authorization Configuration

```php
'authorization' => [
    'default_protocol' => 'default',
    
    // Default authorization checks - set to true to enable by default for all audit logs
    'default_authorization_check' => false,        // General authorization check
    'default_treatment_relation_check' => false,   // Treatment relation check (healthcare specific)
    'default_consent_check' => false,              // Consent check (GDPR/privacy specific)
],
```

### Authorization Check Types

The package supports three types of authorization checks that can be enabled by default:

1. **General Authorization Check** (`default_authorization_check`)
   - Tracks whether proper authentication/authorization was verified before the action
   - Useful for general security compliance

2. **Treatment Relation Check** (`default_treatment_relation_check`) 
   - Healthcare-specific: tracks whether the actor has a treatment relationship with the patient
   - Required for NEN 7513 compliance in healthcare environments

3. **Consent Check** (`default_consent_check`)
   - GDPR/privacy-specific: tracks whether proper consent was obtained for the action
   - Important for privacy regulation compliance

### Per-Environment Authorization Settings

```php
'authorization' => [
    'default_authorization_check' => env('AUDIT_DEFAULT_AUTH_CHECK', false),
    'default_treatment_relation_check' => env('AUDIT_DEFAULT_TREATMENT_CHECK', false),
    'default_consent_check' => env('AUDIT_DEFAULT_CONSENT_CHECK', false),
],
```

Then in `.env`:

```env
# Development - loose authorization tracking
AUDIT_DEFAULT_AUTH_CHECK=false
AUDIT_DEFAULT_TREATMENT_CHECK=false
AUDIT_DEFAULT_CONSENT_CHECK=false

# Production - strict authorization tracking
AUDIT_DEFAULT_AUTH_CHECK=true
AUDIT_DEFAULT_TREATMENT_CHECK=true  
AUDIT_DEFAULT_CONSENT_CHECK=true
```

## UI Configuration

For the web dashboard:

```php
'ui' => [
    'title' => env('AUDIT_LOG_TITLE', 'Audit Logs'),
    'logo' => [
        'type' => env('AUDIT_LOG_LOGO_TYPE', 'file'), // 'file' or 'url'
        'path' => env('AUDIT_LOG_LOGO_PATH', '/images/logo.png'),
        'alt' => env('AUDIT_LOG_LOGO_ALT', 'Logo'),
    ],
    'expanded_row_color' => env('AUDIT_LOG_EXPANDED_COLOR', '#fef3c7'),
],
```

## Model-Specific Configuration

Override global settings per model:

```php
class Patient extends Model
{
    use Auditable;
    
    // Override global audit settings
    protected static function getAuditableActions(): array
    {
        return [
            'read' => true,     // Always log reads for patients
            'create' => true,
            'update' => true,
            'delete' => true,
        ];
    }
    
    // Override CREATE data logging
    protected function shouldLogCreateData(): bool
    {
        return true;
    }
}
```

## Performance Optimization

### Database Indexes

Add indexes for better query performance:

```sql
CREATE INDEX idx_audit_timestamp ON audit_logs(timestamp);
CREATE INDEX idx_audit_object ON audit_logs(object_type, object_id);
CREATE INDEX idx_audit_actor ON audit_logs(actor_type, actor_id);
```

### Cleanup Frequency

```php
'retention' => [
    'cleanup_schedule' => '0 */6 * * *', // Every 6 hours
],
```

## Environment Examples

### Development
```php
'default_audit' => [
    'read' => false,
    'log_create_data' => false,
],
'retention' => [
    'default_days' => 30,
],
'enable_masking' => false,
```

### Production
```php
'default_audit' => [
    'read' => true,
    'log_create_data' => true,
],
'retention' => [
    'default_days' => 2555, // 7 years
],
'enable_masking' => true,
```

## Next Steps

- [Set up automatic logging](automatic-logging.md)
- [Configure web dashboard](web-dashboard.md)  
- [Learn about NEN 7513 compliance](nen7513-compliance.md)
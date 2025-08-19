# Manual Logging

Use the `AuditLog` facade for precise control over audit logging.

## Basic Usage

```php
use CbyteDigital\Nen7513AuditLogging\Facades\AuditLog;

// Simple logging
AuditLog::read($patient);
AuditLog::created($patient);
AuditLog::updated($patient);
AuditLog::deleted($patient);
AuditLog::execute($patient, 'custom_action');
```

## Chainable Context Methods

### Actor Context

```php
// Specify who performed the action
AuditLog::forActor($user)->read($patient);
```

### Success/Failure

```php
// Mark as successful
AuditLog::successful(['records' => 10])->execute($patient, 'export');

// Mark as failed
AuditLog::failed('Permission denied')->read($patient);
```

### Additional Details

```php
// Add custom metadata
AuditLog::withDetail([
    'export_format' => 'PDF',
    'page_count' => 15
])->execute($patient, 'report');
```

### Authorization Context

```php
// Authorization checks
AuditLog::withAuthorization(true)->read($patient);
AuditLog::withTreatmentRelation(true)->read($patient);
AuditLog::withConsent(true)->read($patient);

// Full authorization in one call
AuditLog::withFullAuthorization('oauth2', true, true)->read($patient);
```

### Emergency Access

```php
AuditLog::emergency(
    'Patient emergency - immediate access required',
    'Dr. Emergency (ID: 456)'
)->read($patient);
```

## State Snapshots

Capture "before" and "after" states:

```php
// Capture original state
$originalState = AuditLog::originalState($patient, ['prescriptions']);

// Perform operations
$patient->update(['status' => 'discharged']);

// Log with state comparison
AuditLog::withOriginalState($originalState)
    ->successful(['reason' => 'Recovery complete'])
    ->execute($patient, 'discharged');
```

## Examples

### API Access Logging

```php
public function show(Patient $patient, Request $request)
{
    if (!$this->canAccess($patient, $request->user())) {
        AuditLog::forActor($request->user())
            ->withAuthorization(false)
            ->failed('Insufficient permissions')
            ->read($patient);
            
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    AuditLog::forActor($request->user())
        ->withAuthorization(true)
        ->successful(['via' => 'api'])
        ->read($patient);

    return new PatientResource($patient);
}
```

### Complex Operations

```php
class PatientDischargeService
{
    public function discharge(Patient $patient, User $doctor): void
    {
        $originalState = AuditLog::originalState($patient);
        
        try {
            $patient->update(['status' => 'discharged']);
            $this->generatePapers($patient);
            
            AuditLog::forActor($doctor)
                ->withOriginalState($originalState)
                ->successful(['papers_generated' => true])
                ->execute($patient, 'discharged');
                
        } catch (Exception $e) {
            AuditLog::forActor($doctor)
                ->failed($e->getMessage())
                ->execute($patient, 'discharge_failed');
                
            throw $e;
        }
    }
}
```

### Batch Operations

```php
AuditLog::forActor($user)
    ->execute(null, 'batch_started', "Processing {$count} records");

foreach ($records as $record) {
    // Process without individual logging
    $record->withoutAuditing(function () use ($record) {
        $record->update($data);
    });
}

AuditLog::forActor($user)
    ->successful(['processed' => $count])
    ->execute(null, 'batch_completed');
```

## Available Methods

### Action Methods
- `created($object, ?string $name = null)`
- `read($object, ?string $name = null)`
- `updated($object, array $changedFields = [], ?string $name = null)`
- `deleted($object, ?string $name = null)`
- `execute($object, ?string $name = null)`

### Context Methods (Chainable)
- `forActor(Model $actor)`
- `withDetail(array $detail)`
- `successful(array $detail = [])`
- `failed(?string $reason = null)`
- `emergency(string $reason, string $authorizedBy)`
- `withAuthorization(bool $authorized = true)`
- `withTreatmentRelation(bool $hasRelation = true)`
- `withConsent(bool $hasConsent = true)`
- `withFullAuthorization(string $authProtocol = 'oauth2', bool $treatmentRelation = true, bool $consent = true)`

### State Methods
- `originalState(Model $model, array $relations = [])` (static)
- `withOriginalState(StateSnapshot $originalState)`

## Next Steps

- [Learn parent-child logging](parent-child-logging.md)
- [Configure global settings](configuration.md)
- [Set up web dashboard](web-dashboard.md)
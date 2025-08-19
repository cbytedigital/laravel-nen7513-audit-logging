# API Reference

Complete method documentation for the audit logging package.

## AuditLog Facade

Main entry point for manual audit logging.

### Action Methods

#### `created(Model $object, ?string $name = null): void`
Log object creation.

```php
AuditLog::created($patient);
AuditLog::created($patient, 'Patient registered via API');
```

#### `read(Model $object, ?string $name = null): void`
Log data access.

```php
AuditLog::read($patient);
AuditLog::read($patient, 'Medical record accessed');
```

#### `updated(Model $object, array $changedFields = [], ?string $name = null): void`
Log data updates.

```php
AuditLog::updated($patient);
AuditLog::updated($patient, ['status', 'notes']);
```

#### `deleted(Model $object, ?string $name = null): void`
Log data deletion.

```php
AuditLog::deleted($patient);
AuditLog::deleted($patient, 'Patient record archived');
```

#### `execute(Model|null $object, string $action, ?string $name = null): void`
Log custom actions.

```php
AuditLog::execute($patient, 'exported');
AuditLog::execute(null, 'system_backup');
```

### Context Methods (Chainable)

#### `forActor(Model $actor): static`
Specify who performed the action.

```php
AuditLog::forActor($user)->read($patient);
```

#### `withDetail(array $detail): static`
Add custom metadata.

```php
AuditLog::withDetail(['export_format' => 'PDF'])->execute($patient, 'export');
```

#### `failed(?string $reason = null): static`
Mark action as failed.

```php
AuditLog::failed('Permission denied')->read($patient);
```

#### `successful(array $detail = []): static`
Mark action as successful.

```php
AuditLog::successful(['records' => 150])->execute($patient, 'export');
```

#### `emergency(string $reason, string $authorizedBy): static`
Log emergency access.

```php
AuditLog::emergency('Patient emergency', 'Dr. Smith (ID: 123)')->read($patient);
```

#### `withAuthorization(bool $authorized = true): static`
Set authorization status.

```php
AuditLog::withAuthorization(true)->read($patient);
```

#### `withTreatmentRelation(bool $hasRelation = true): static`
Set treatment relationship status.

```php
AuditLog::withTreatmentRelation(true)->read($patient);
```

#### `withConsent(bool $hasConsent = true): static`
Set consent status.

```php
AuditLog::withConsent(true)->read($patient);
```

#### `withFullAuthorization(string $authProtocol = 'oauth2', bool $treatmentRelation = true, bool $consent = true): static`
Set full authorization context.

```php
AuditLog::withFullAuthorization('oauth2', true, true)->read($patient);
```

### State Snapshot Methods

#### `originalState(Model $model, array $relations = []): StateSnapshot` (Static)
Capture model state.

```php
$originalState = AuditLog::originalState($patient, ['prescriptions']);
```

#### `withOriginalState(StateSnapshot $originalState): static`
Use captured state for comparison.

```php
AuditLog::withOriginalState($originalState)->updated($patient);
```

## Auditable Trait

Methods for models using the `Auditable` trait.

### Required Methods

#### `getAuditName(): string`
Define how the model appears in logs.

```php
public function getAuditName(): string
{
    return $this->first_name . ' ' . $this->last_name;
}
```

### Optional Methods

#### `getAuditableActions(): array`
Control which operations to log.

```php
protected static function getAuditableActions(): array
{
    return [
        'read' => true,
        'create' => true,
        'update' => true,
        'delete' => true,
    ];
}
```

#### `shouldLogCreateData(): bool`
Control CREATE data logging.

```php
protected function shouldLogCreateData(): bool
{
    return false;
}
```

#### `getAuditCreateData(): array`
Customize CREATE data.

```php
protected function getAuditCreateData(): array
{
    $data = parent::getAuditCreateData();
    unset($data['password']);
    return $data;
}
```

### Manual Logging Methods

#### `auditCreate(?AuditContext $context = null): void`
#### `auditRead(?AuditContext $context = null): void`
#### `auditUpdate(?AuditContext $context = null): void`
#### `auditDelete(?AuditContext $context = null): void`
#### `auditExecute(string $action, ?AuditContext $context = null): void`

```php
$patient->auditRead();
$patient->auditExecute('custom_action');
```

### Utility Methods

#### `withoutAuditing(\Closure $callback): mixed`
Disable auditing temporarily.

```php
$patient->withoutAuditing(function () use ($patient) {
    $patient->update(['internal_field' => 'value']);
});
```

## ParentAuditable Trait

### Configuration Methods

#### `getParentAuditConfig(): array` (Required Static)
Configure parent logging.

```php
protected static function getParentAuditConfig(): array
{
    return [
        'parent_relation' => 'client',
        'actions' => ['create', 'update', 'delete'],
        'context_fields' => ['notes', 'status'],
    ];
}
```

#### `getChildAuditName(): ?string`
Customize child name in logs.

```php
protected function getChildAuditName(): ?string
{
    return "Note: {$this->title}";
}
```

### Manual Methods

#### `auditParentAction(string $customAction, array $additionalContext = []): void`
Manually trigger parent logging.

```php
$history->auditParentAction('custom_action', ['context' => 'data']);
```

## AuditActor Trait

For actor models (User, SystemActor, etc.).

### Required Methods

#### `getAuditActorName(): string`
Actor name for logs.

```php
public function getAuditActorName(): string
{
    return $this->name;
}
```

#### `getAuditActorRole(): string`
Actor role for logs.

```php
public function getAuditActorRole(): string
{
    return $this->role ?? 'user';
}
```

## Value Objects

### AuditContext

#### `successful(array $detail = []): static`
Create successful context.

```php
$context = AuditContext::successful(['count' => 50]);
```

#### `failed(string $reason, array $detail = []): static`
Create failed context.

```php
$context = AuditContext::failed('Permission denied');
```

#### `emergency(string $reason, string $authorizedBy): static`
Create emergency context.

```php
$context = AuditContext::emergency('Cardiac arrest', 'Dr. Smith');
```

### StateSnapshot

#### `capture(Model $model, array $relations = []): static`
Capture model state.

```php
$snapshot = StateSnapshot::capture($patient, ['prescriptions']);
```

## Enums

### AuditAction
- `AuditAction::CREATE`
- `AuditAction::READ`
- `AuditAction::UPDATE`
- `AuditAction::DELETE`
- `AuditAction::EXECUTE`

### AuditOutcome
- `AuditOutcome::SUCCESS`
- `AuditOutcome::FAILURE`
- `AuditOutcome::EMERGENCY`

## Next Steps

- [Learn automatic logging](automatic-logging.md)
- [Explore manual logging](manual-logging.md)
- [Configure the package](configuration.md)
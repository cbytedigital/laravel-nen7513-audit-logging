# NEN 7513 Compliance

This package is designed to comply with the Dutch NEN 7513 standard for healthcare audit logging.

## About NEN 7513

NEN 7513 is the Dutch national standard for healthcare audit logging. It requires:

- **Complete logging** - All access to patient data must be logged
- **Specific data fields** - Mandatory fields for each log entry
- **Retention periods** - Minimum 2 years, maximum 15 years
- **Data integrity** - Logs must be immutable and verifiable

## Compliance Features

### ✅ Required Data Fields

The package captures all mandatory NEN 7513 fields:

| NEN 7513 Field | Package Field | Description |
|----------------|---------------|-------------|
| Event Date/Time | `timestamp` | ISO 8601 timestamp |
| User ID | `actor_id` | Unique user identifier |
| User Name | `actor_name` | Display name |
| User Role | `actor_role` | User role/function |
| Action | `action` | Type of action performed |
| Object Type | `object_type` | Type of data accessed |
| Object ID | `object_id` | Unique object identifier |
| Object Name | `object_name` | Human-readable description |
| Outcome | `outcome` | Success or failure |
| Source | `source_id`, `source_type` | System identifier |
| Network Address | `source_ip` | Client IP address |
| Authorization | `authorization_*` | Treatment relation, consent |

### ✅ Data Integrity

- **Immutable logs** - Database triggers prevent modification after creation
- **Integrity hash** - Each log entry includes a cryptographic hash
- **Secure deletion** - Only authorized cleanup through SecureAuditCleanupService

### ✅ Retention Management

```php
// NEN 7513 compliant retention
'retention' => [
    'min_days' => 730,      // 2 years minimum
    'max_days' => 5475,     // 15 years maximum
    'default_days' => 730,  // Your policy
],
```

### ✅ Actor Traceability

Complete tracking of who performed each action:

```php
// User actors
AuditLog::forActor($user)->read($patient);

// System actors
$systemActor = SystemActor::create(['name' => 'Backup System']);
AuditLog::forActor($systemActor)->execute($patient, 'backup');

// Guest actors
$guestActor = GuestActor::create(['ip_address' => '192.168.1.100']);
AuditLog::forActor($guestActor)->failed('Unauthorized')->read($patient);
```

### ✅ Authorization Tracking

Track treatment relationships and consent:

```php
// Full authorization tracking
AuditLog::withTreatmentRelation(true)
    ->withConsent(true)
    ->withAuthorization(true)
    ->read($patient);

// Emergency access
AuditLog::emergency(
    'Patient cardiac arrest - immediate access required',
    'Dr. Emergency (ID: 456)'
)->read($patient);
```

### ✅ Privacy Protection

Automatic masking compliant with GDPR and NEN 7513:

```php
'masking' => [
    'sensitive_fields' => [
        'ssn', 'bsn', 'patient_number',
        'birth_date', 'address', 'phone', 'email',
    ],
],
```

## Implementation Examples

### Patient Data Access

```php
class Patient extends Model
{
    use Auditable;
    
    // NEN 7513 requires logging all patient data access
    protected static function getAuditableActions(): array
    {
        return [
            'read' => true,     // ✅ Log all access
            'create' => true,
            'update' => true,
            'delete' => true,
        ];
    }
    
    public function getAuditName(): string
    {
        // Use patient number for privacy
        return "Patient #{$this->patient_number}";
    }
}
```

### Emergency Access

```php
class EmergencyAccessService
{
    public function grantAccess(Patient $patient, User $doctor, string $reason): void
    {
        AuditLog::forActor($doctor)
            ->emergency($reason, "Supervisor: {$doctor->supervisor}")
            ->read($patient);
        
        // Grant access and schedule review
        $this->grantTemporaryAccess($doctor, $patient);
        $this->scheduleReview($doctor, $patient, $reason);
    }
}
```

### Treatment Relationship Validation

```php
class PatientAccessService
{
    public function accessRecord(Patient $patient, User $user): void
    {
        $hasTreatmentRelation = $this->checkTreatmentRelation($user, $patient);
        $hasConsent = $patient->hasConsent();
        
        AuditLog::forActor($user)
            ->withTreatmentRelation($hasTreatmentRelation)
            ->withConsent($hasConsent)
            ->withAuthorization($hasTreatmentRelation && $hasConsent)
            ->read($patient);
        
        if (!$hasTreatmentRelation || !$hasConsent) {
            throw new UnauthorizedAccessException();
        }
    }
}
```

## Compliance Reports

### Access Report

```php
class ComplianceReportService
{
    public function generateAccessReport(Patient $patient, Carbon $start, Carbon $end): array
    {
        return DB::table('audit_logs')
            ->where('object_type', 'patients')
            ->where('object_id', $patient->id)
            ->whereBetween('timestamp', [$start, $end])
            ->get()
            ->map(function ($log) {
                return [
                    'timestamp' => $log->timestamp,
                    'user' => $log->actor_name,
                    'action' => $log->action,
                    'authorized' => $log->authorization_check ?? false,
                    'treatment_relation' => $log->treatment_relation_check ?? false,
                    'outcome' => $log->outcome,
                ];
            })
            ->toArray();
    }
}
```

### Emergency Access Report

```php
public function getEmergencyAccess(Carbon $start, Carbon $end): array
{
    return DB::table('audit_logs')
        ->where('outcome', 'emergency')
        ->whereBetween('timestamp', [$start, $end])
        ->get()
        ->toArray();
}
```

## Next Steps

- [Configure retention policies](configuration.md#retention-policy-nen-7513)
- [Set up compliance monitoring](web-dashboard.md)
- [Implement emergency procedures](manual-logging.md#emergency-access)
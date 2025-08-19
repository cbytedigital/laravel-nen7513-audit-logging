# Parent-Child Logging

The `ParentAuditable` trait automatically logs child model changes on their parent models.

## Basic Setup

Add the trait to your child model:

```php
<?php

use CbyteDigital\Nen7513AuditLogging\Traits\ParentAuditable;
use Illuminate\Database\Eloquent\Model;

class ClientHistory extends Model
{
    use ParentAuditable;
    
    // Required: Configure parent relationship
    protected static function getParentAuditConfig(): array
    {
        return [
            'parent_relation' => 'client',
            'actions' => ['create', 'update', 'delete'],
        ];
    }
    
    // Define the relationship
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
```

## Configuration Options

### Required Configuration

```php
protected static function getParentAuditConfig(): array
{
    return [
        'parent_relation' => 'client',  // Method name for parent relationship
        'actions' => ['create', 'update', 'delete'], // Which actions to log
    ];
}
```

### Optional Configuration

```php
protected static function getParentAuditConfig(): array
{
    return [
        'parent_relation' => 'client',
        'actions' => ['create', 'update', 'delete'],
        
        // Optional: Specify which fields to include
        'context_fields' => ['notes', 'status', 'date'],
        
        // Optional: Custom child name in logs
        'child_name' => 'History Entry',
        
        // Optional: Include timestamps
        'include_timestamps' => true,
    ];
}
```

## How It Works

When child models change, they're automatically logged on the parent:

```php
$client = Client::find(1);

// This creates a ClientHistory AND logs it on the Client
$history = $client->history()->create([
    'notes' => 'Client called about appointment',
    'type' => 'phone_call'
]);
```

## Output Examples

### Child Creation
```json
{
  "action": "create",
  "object_type": "clients",
  "object_id": "123",
  "object_name": "John Doe",
  "object_detail": {
    "operation": "child_create",
    "child_type": "client_histories",
    "child_data": {
      "notes": "Client called about appointment",
      "type": "phone_call"
    }
  }
}
```

### Child Update
```json
{
  "action": "update",
  "object_type": "clients", 
  "object_id": "123",
  "object_detail": {
    "operation": "child_update",
    "child_type": "client_histories",
    "changed_fields": ["notes"],
    "changes": {
      "notes": {
        "from": "Initial call",
        "to": "Follow-up completed"
      }
    }
  }
}
```

## Manual Parent Logging

You can manually trigger parent logging:

```php
$history->auditParentAction('custom_action', [
    'reason' => 'Manual update',
    'context' => 'additional data'
]);
```

## Advanced Example

```php
class Order extends Model
{
    use Auditable; // Parent needs this
    
    public function getAuditName(): string
    {
        return "Order #{$this->order_number}";
    }
}

class OrderLine extends Model
{
    use ParentAuditable;
    
    protected static function getParentAuditConfig(): array
    {
        return [
            'parent_relation' => 'order',
            'actions' => ['create', 'update', 'delete'],
            'context_fields' => ['product_name', 'quantity', 'price'],
        ];
    }
    
    // Optional: Custom child name
    protected function getChildAuditName(): ?string
    {
        return "{$this->quantity}x {$this->product_name}";
    }
}
```

## Requirements

- Parent model must use `Auditable` trait
- Child model must use `ParentAuditable` trait  
- Parent relationship must exist and be accessible
- Parent must implement `getAuditName()` method

## Available Methods

### Configuration
- `getParentAuditConfig()` (required static method)
- `getChildAuditName()` (optional method)

### Manual Logging
- `auditParentAction(string $customAction, array $additionalContext = [])`

## Next Steps

- [Configure global settings](configuration.md)
- [Learn manual logging](manual-logging.md)
- [Set up web dashboard](web-dashboard.md)
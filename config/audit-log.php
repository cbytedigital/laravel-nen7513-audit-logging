<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Database table configuration for audit logs
    |
    */
    'table_name' => 'audit_logs',

    /*
    |--------------------------------------------------------------------------
    | Retention Configuration (NEN 7513 Compliance)
    |--------------------------------------------------------------------------
    |
    | NEN 7513 requires minimum 2 years, maximum 15 years retention
    |
    */
    'retention' => [
        'min_days' => 730,          // 2 years minimum (NEN 7513)
        'max_days' => 5475,         // 15 years maximum (NEN 7513)
        'default_days' => 730,      // Default retention period
        'cleanup_schedule' => '0 2 * * *', // Daily at 2:00 AM - cron expression for automatic cleanup
        'batch_size' => 1000,       // Records per batch during cleanup
    ],

    /*
    |--------------------------------------------------------------------------
    | Masking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for masking sensitive data
    |
    */
    'enable_masking' => true,
    'masking' => [
        'sensitive_fields' => [
            // Personal information fields that should be masked in object_detail
            'first_name', 'last_name', 'name', 'full_name',
            'email', 'phone', 'mobile', 'telephone',
            'address', 'street', 'city', 'postal_code', 'zipcode',
            'ssn', 'bsn', 'patient_number', 'patient_id',
            'birth_date', 'date_of_birth', 'dob'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Audit Behavior
    |--------------------------------------------------------------------------
    |
    | Default settings for which CRUD operations should be audited.
    | These can be overridden per model using getAuditableActions() method.
    |
    */
    'default_audit' => [
        'read' => false,   // Default false to prevent spam on overview pages
        'create' => true,  // Usually you want to log creation
        'update' => true,  // Usually you want to log updates  
        'delete' => true,  // Usually you want to log deletions
        
        // Data logging configuration
        'log_create_data' => true,    // Log full model data on CREATE (default ON)
    ],
    
    'enable_auto_logging' => true,

    /*
    |--------------------------------------------------------------------------
    | Source Configuration
    |--------------------------------------------------------------------------
    |
    | Default source information for audit logs
    |
    */
    'source' => [
        'default_id' => env('APP_NAME', 'laravel_app'),
        'default_type' => 'laravel_application',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | Default authorization configuration
    |
    */
    'authorization' => [
        'default_protocol' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the audit log web interface
    |
    */
    'ui' => [
        'title' => env('AUDIT_LOG_TITLE', 'Audit Logs'),
        'logo' => [
            'type' => env('AUDIT_LOG_LOGO_TYPE', 'file'), // 'file' or 'url'
            'path' => env('AUDIT_LOG_LOGO_PATH', '/images/logo.png'), // File path or URL
            'alt' => env('AUDIT_LOG_LOGO_ALT', 'Logo'),
        ],
        'expanded_row_color' => env('AUDIT_LOG_EXPANDED_COLOR', '#fef3c7'), // Default yellow-100
    ],
];
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Event info (REQUIRED by NEN 7513)
            $table->enum('action', ['C', 'R', 'U', 'D', 'E'])->comment('Create, Read, Update, Delete, Execute');
            $table->timestamp('event_time', 6)->useCurrent()->comment('Exact timestamp of the event');
            $table->enum('outcome', ['success', 'failure', 'unknown'])->default('success');
            
            // Actor info (polymorphic)
            $table->string('actor_type')->comment('Model class of the actor');
            $table->string('actor_id')->comment('ID of the actor (polymorphic)');
            $table->string('actor_name')->comment('Name of actor (cached for history)');
            $table->string('actor_role', 100)->nullable()->comment('Role of the actor');
            
            // Object info (REQUIRED by NEN 7513)
            $table->string('object_type', 100)->comment('Type of object (patient, record, etc)');
            $table->string('object_id')->comment('ID of the object');
            $table->string('object_name')->nullable()->comment('Name of object (masked)');
            $table->json('object_detail')->nullable()->comment('Extra details like changes');
            
            // Authorization info (REQUIRED by NEN 7513)
            $table->string('authorization_protocol', 100)->default('default');
            $table->boolean('authorization_check')->default(true);
            $table->boolean('treatment_relation_check')->default(false);
            $table->boolean('consent_check')->default(false);
            
            // Source info (REQUIRED by NEN 7513)
            $table->string('source_id', 100)->default('main_app');
            $table->string('source_type', 50)->default('laravel_app');
            
            // Context information
            $table->string('ip_address', 45)->nullable()->comment('IP address of user');
            $table->text('user_agent')->nullable()->comment('Browser user agent');
            
            // Data integrity (NEN 7513 compliance)
            $table->string('integrity_hash', 64)->nullable()->comment('SHA-256 hash for data integrity verification');
            
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at - audit logs are immutable (NEN 7513 requirement)
            
            // Indices for performance
            $table->index(['actor_type', 'actor_id'], 'idx_actor');
            $table->index(['object_type', 'object_id'], 'idx_object');
            $table->index('event_time', 'idx_event_time');
            $table->index(['action', 'event_time'], 'idx_action_time');
            $table->index(['outcome', 'event_time'], 'idx_outcome_time');
            $table->index('integrity_hash', 'idx_integrity_hash');
        });

        // Add database triggers for NEN 7513 compliance
        // These prevent modification/deletion of audit logs to ensure immutability
        $this->addImmutabilityTriggers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers first
        $this->dropImmutabilityTriggers();
        
        // Drop the table
        Schema::dropIfExists('audit_logs');
    }

    /**
     * Add database triggers to prevent audit log modification
     * Required for NEN 7513 compliance - audit logs must be immutable
     */
    private function addImmutabilityTriggers(): void
    {
        // Only add triggers for MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            // Prevent updates to audit logs
            DB::unprepared('
                CREATE TRIGGER prevent_audit_log_updates
                BEFORE UPDATE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE "45000" 
                    SET MESSAGE_TEXT = "NEN 7513 VIOLATION: Audit logs are immutable and cannot be modified";
                END
            ');

            // Prevent ALL deletions - retention cleanup uses special method
            DB::unprepared('
                CREATE TRIGGER prevent_audit_log_deletes
                BEFORE DELETE ON audit_logs
                FOR EACH ROW
                BEGIN
                    -- Block ALL deletions by default - force use of secure cleanup method
                    SIGNAL SQLSTATE "45000" 
                    SET MESSAGE_TEXT = "NEN 7513 VIOLATION: Direct deletion forbidden - use audit:cleanup command";
                END
            ');
        }
    }

    /**
     * Drop the immutability triggers
     */
    private function dropImmutabilityTriggers(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_audit_log_updates');
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_audit_log_deletes');
        }
    }
};
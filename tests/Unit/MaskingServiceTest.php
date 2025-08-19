<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Unit;

use CbyteDigital\Nen7513AuditLogging\Services\MaskingService;
use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;

class MaskingServiceTest extends TestCase
{
    private MaskingService $maskingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->maskingService = new MaskingService();
    }

    /** @test */
    public function it_masks_audit_log_object_name()
    {
        $log = (object) [
            'id' => 1,
            'object_name' => 'Jan Jansen',
            'actor_name' => 'Dr. Smith',
        ];

        $maskedLog = $this->maskingService->maskAuditLogForDisplay($log);

        $this->assertEquals('J** J*****', $maskedLog->object_name);
        $this->assertEquals('Dr. Smith', $maskedLog->actor_name); 
            
        // Original should be unchanged
        $this->assertEquals('Jan Jansen', $log->object_name);
    }

    /** @test */
    public function it_masks_object_detail_json()
    {
        $log = (object) [
            'id' => 1,
            'object_detail' => json_encode([
                'first_name' => 'Maria',
                'last_name' => 'van der Berg',
                'email' => 'maria@example.com',
                'age' => 35,
                'status' => 'active'
            ])
        ];

        $maskedLog = $this->maskingService->maskAuditLogForDisplay($log);
        $maskedDetail = json_decode($maskedLog->object_detail, true);

        $this->assertEquals('M****', $maskedDetail['first_name']);
        $this->assertEquals('v** d** B***', $maskedDetail['last_name']);
        $this->assertEquals('m****@e******.c**', $maskedDetail['email']);
        $this->assertEquals(35, $maskedDetail['age']); // Not sensitive
        $this->assertEquals('active', $maskedDetail['status']); // Not sensitive
    }

    /** @test */
    public function it_does_not_mask_actor_names()
    {
        $log = (object) [
            'id' => 1,
            'actor_name' => 'Dr. Jan Jansen',
            'object_name' => 'Patient Maria',
        ];

        $maskedLog = $this->maskingService->maskAuditLogForDisplay($log);

        $this->assertEquals('Dr. Jan Jansen', $maskedLog->actor_name); // Actor names are never masked
        $this->assertEquals('P****** M****', $maskedLog->object_name); // Object names are masked
    }

    /** @test */
    public function it_handles_null_values()
    {
        $log = (object) [
            'id' => 1,
            'object_name' => null,
            'object_detail' => null,
            'actor_name' => null,
        ];

        $maskedLog = $this->maskingService->maskAuditLogForDisplay($log);

        $this->assertNull($maskedLog->object_name);
        $this->assertNull($maskedLog->object_detail);
        $this->assertNull($maskedLog->actor_name);
    }

    /** @test */
    public function it_masks_collection_of_logs()
    {
        $logs = collect([
            (object) ['id' => 1, 'object_name' => 'Jan Jansen'],
            (object) ['id' => 2, 'object_name' => 'Maria Vermeulen'],
        ]);

        $maskedLogs = $this->maskingService->maskAuditLogsForDisplay($logs);

        $this->assertEquals('J** J*****', $maskedLogs[0]->object_name);
        $this->assertEquals('M**** V********', $maskedLogs[1]->object_name);
        
        // Original should be unchanged
        $this->assertEquals('Jan Jansen', $logs[0]->object_name);
    }

    /** @test */
    public function it_handles_special_characters_correctly()
    {
        $log = (object) [
            'id' => 1,
            'object_name' => 'René van der Müller',
            'object_detail' => json_encode([
                'first_name' => 'José',
                'last_name' => 'García-López',
                'email' => 'rené@müller.com',
                'address' => 'Straße 123, Düsseldorf',
                'notes' => 'Patiënt met speciale karakters' // This won't be masked as it's not in sensitive_fields
            ])
        ];

        $maskedLog = $this->maskingService->maskAuditLogForDisplay($log);
        $maskedDetail = json_decode($maskedLog->object_detail, true);

        // Test object_name with special characters
        $this->assertEquals('R*** v** d** M*****', $maskedLog->object_name);
        
        // Test object_detail with special characters (only sensitive fields are masked)
        $this->assertEquals('J***', $maskedDetail['first_name']);
        $this->assertEquals('G*****-L****', $maskedDetail['last_name']);
        $this->assertEquals('r***@m*****.c**', $maskedDetail['email']);
        $this->assertEquals('S***** 1**, D*********', $maskedDetail['address']);
        $this->assertEquals('Patiënt met speciale karakters', $maskedDetail['notes']); // Not masked
    }

    /** @test */
    public function it_respects_masking_configuration_when_disabled()
    {
        // Mock config to disable masking
        config(['audit-log.enable_masking' => false]);

        $log = (object) [
            'id' => 1,
            'object_name' => 'Jan Jansen',
            'actor_name' => 'Dr. Smith',
        ];

        $maskedLog = $this->maskingService->maskAuditLogForDisplay($log);

        // Should not be masked when disabled
        $this->assertEquals('Jan Jansen', $maskedLog->object_name);
        $this->assertEquals('Dr. Smith', $maskedLog->actor_name); // Actor names are never masked anyway
    }
}

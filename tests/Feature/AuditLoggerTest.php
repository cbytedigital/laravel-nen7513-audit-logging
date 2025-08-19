<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Feature;

use CbyteDigital\Nen7513AuditLogging\Services\AuditLogger;
use CbyteDigital\Nen7513AuditLogging\Enums\AuditAction;
use CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome;
use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuditLoggerTest extends TestCase
{
    private AuditLogger $auditLogger;
    private AuditLoggerTestUser $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->auditLogger = app(AuditLogger::class);
        $this->testUser = new AuditLoggerTestUser();
        $this->testUser->id = 1;
        $this->testUser->name = 'Test User';
        $this->testUser->role = 'doctor';
    }

    /** @test */
    public function it_logs_basic_audit_event()
    {
        $this->auditLogger->log(
            AuditAction::READ,
            $this->testUser,
            'patient',
            '123',
            'Jan Jansen'
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'R',
            'actor_type' => AuditLoggerTestUser::class,
            'actor_id' => 1,
            'actor_name' => 'Test User',
            'actor_role' => 'doctor',
            'object_type' => 'patient',
            'object_id' => '123',
            'object_name' => 'Jan Jansen',
            'outcome' => 'success',
        ]);
    }

    /** @test */
    public function it_logs_with_context()
    {
        $context = new AuditContext(
            detail: ['reason' => 'emergency'],
            outcome: AuditOutcome::FAILURE,
            authProtocol: 'oauth2',
            treatmentRelationCheck: true
        );

        $this->auditLogger->log(
            AuditAction::READ,
            $this->testUser,
            'patient',
            '123',
            'Jan Jansen',
            $context
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'R',
            'outcome' => 'failure',
            'authorization_protocol' => 'oauth2',
            'treatment_relation_check' => true,
            'object_detail' => json_encode(['reason' => 'emergency']),
        ]);
    }

    /** @test */
    public function it_uses_convenience_methods()
    {
        $this->auditLogger->logRead($this->testUser, 'patient', '123', 'Jan Jansen');
        $this->auditLogger->logCreate($this->testUser, 'patient', '124', 'Piet Pietersen');
        $this->auditLogger->logUpdate($this->testUser, 'patient', '125', 'Klaas Klaassen');
        $this->auditLogger->logDelete($this->testUser, 'patient', '126', 'Marie Vermeulen');

        $this->assertEquals(4, DB::table('audit_logs')->count());
        
        $this->assertDatabaseHas('audit_logs', ['action' => 'R', 'object_id' => '123']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'C', 'object_id' => '124']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'U', 'object_id' => '125']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'D', 'object_id' => '126']);
    }

    /** @test */
    public function it_stores_names_unmasked_in_database()
    {
        $this->auditLogger->log(
            AuditAction::READ,
            $this->testUser,
            'patient',
            '123',
            'Jan Jansen van der Berg'
        );

        $log = DB::table('audit_logs')->first();
        // Data should be stored unmasked in database for audit purposes
        $this->assertEquals('Jan Jansen van der Berg', $log->object_name);
    }

    /** @test */
    public function it_throws_exception_for_invalid_actor()
    {
        $invalidActor = new class extends Model {
            public $id = 1;
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement getAuditActorName() method');

        $this->auditLogger->log(
            AuditAction::READ,
            $invalidActor,
            'patient',
            '123'
        );
    }

    /** @test */
    public function it_can_log_bulk_events()
    {
        $events = [
            [
                'action' => AuditAction::READ,
                'actor' => $this->testUser,
                'object_type' => 'patient',
                'object_id' => '1',
                'object_name' => 'Patient 1',
                'context' => new AuditContext(),
            ],
            [
                'action' => AuditAction::READ,
                'actor' => $this->testUser,
                'object_type' => 'patient',
                'object_id' => '2',
                'object_name' => 'Patient 2',
                'context' => new AuditContext(),
            ],
        ];

        $this->auditLogger->logBulk($events);

        $this->assertEquals(2, DB::table('audit_logs')->count());
        $this->assertDatabaseHas('audit_logs', ['object_id' => '1']);
        $this->assertDatabaseHas('audit_logs', ['object_id' => '2']);
    }
}

/**
 * Test helper class
 */
class AuditLoggerTestUser extends Model
{
    use \CbyteDigital\Nen7513AuditLogging\Traits\AuditActor;

    protected $table = 'users';
    public $timestamps = false;

    public function getAuditActorName(): string
    {
        return $this->name;
    }

    public function getAuditActorRole(): string
    {
        return $this->role;
    }
}
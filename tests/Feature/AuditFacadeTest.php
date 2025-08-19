<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Feature;

use CbyteDigital\Nen7513AuditLogging\Facades\AuditLog;
use CbyteDigital\Nen7513AuditLogging\Services\ActorResolver;
use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuditFacadeTest extends TestCase
{
    private FacadeTestPatient $testPatient;
    private FacadeTestUser $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = new FacadeTestUser();
        $this->testUser->id = 1;
        $this->testUser->name = 'Dr. Smith';
        $this->testUser->role = 'doctor';

        $this->testPatient = new FacadeTestPatient();
        $this->testPatient->id = 123;
        $this->testPatient->first_name = 'Jan';
        $this->testPatient->last_name = 'Jansen';

        // Setup system actor (only for console context)
        app()->bind('audit.system_actor', function() {
            return $this->testUser;
        });
    }

    /** @test */
    public function it_logs_basic_actions()
    {
        AuditLog::read($this->testPatient);
        AuditLog::created($this->testPatient);
        AuditLog::updated($this->testPatient, ['first_name']);
        AuditLog::deleted($this->testPatient);

        $this->assertEquals(4, DB::table('audit_logs')->count());
        
        $actions = DB::table('audit_logs')->pluck('action')->toArray();
        $this->assertContains('R', $actions);
        $this->assertContains('C', $actions);
        $this->assertContains('U', $actions);
        $this->assertContains('D', $actions);
    }

    /** @test */
    public function it_logs_with_specific_actor()
    {
        $adminUser = new FacadeTestUser();
        $adminUser->id = 2;
        $adminUser->name = 'Admin';
        $adminUser->role = 'admin';

        AuditLog::forActor($adminUser)->read($this->testPatient);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => 2,
            'actor_name' => 'Admin',
            'actor_role' => 'admin',
        ]);
    }

    /** @test */
    public function it_logs_with_detail()
    {
        AuditLog::withDetail(['reason' => 'emergency'])->read($this->testPatient);

        $this->assertDatabaseHas('audit_logs', [
            'object_detail' => json_encode(['reason' => 'emergency']),
        ]);
    }

    /** @test */
    public function it_logs_failed_actions()
    {
        AuditLog::failed('no_permission')->read($this->testPatient);

        $this->assertDatabaseHas('audit_logs', [
            'outcome' => 'failure',
            'object_detail' => json_encode(['reason' => 'no_permission']),
        ]);
    }

    /** @test */
    public function it_logs_emergency_access()
    {
        AuditLog::emergency('cardiac_arrest', 'dr_smith')->read($this->testPatient);

        $log = DB::table('audit_logs')->first();
        $detail = json_decode($log->object_detail, true);
        
        $this->assertEquals('emergency_override', $log->authorization_protocol);
        $this->assertEquals(0, $log->treatment_relation_check);
        $this->assertEquals('cardiac_arrest', $detail['reason']);
        $this->assertEquals('dr_smith', $detail['authorized_by']);
        $this->assertTrue($detail['emergency_access']);
    }

    /** @test */
    public function it_logs_with_full_authorization()
    {
        AuditLog::withFullAuthorization('saml2',        true, true)->read($this->testPatient);

        $this->assertDatabaseHas('audit_logs', [
            'authorization_protocol' => 'saml2',
            'authorization_check' => true,
            'treatment_relation_check' => true,
            'consent_check' => true,
        ]);
    }

    /** @test */
    public function it_chains_methods_correctly()
    {
        AuditLog::forActor($this->testUser)
                ->withDetail(['reason' => 'routine_check'])
                ->read($this->testPatient);

        $this->assertDatabaseHas('audit_logs', [
            'actor_name' => 'Dr. Smith',
            'object_detail' => json_encode(['reason' => 'routine_check']),
        ]);
    }

    /** @test */
    public function it_uses_consistent_actor_detection_between_facade_and_trait()
    {
        // Clear all audit logs first
        DB::table('audit_logs')->truncate();
        
        // Create test patient with auditable trait
        $auditablePatient = new AuditableFacadeTestPatient();
        $auditablePatient->id = 456;
        $auditablePatient->first_name = 'Test';
        $auditablePatient->last_name = 'Patient';

        // Both facade and trait should use the same actor resolution
        // In this test context, both will use the system actor from setUp()
        
        // Test 1: Using facade withDetail (should use default actor resolution)
        AuditLog::withDetail(['test' => 'facade'])->read($auditablePatient);

        // Test 2: Using trait auditRead (should use same actor resolution)
        $auditablePatient->auditRead();

        // Verify both logs were created with same actor
        $logs = DB::table('audit_logs')->orderBy('id')->get();
        $this->assertCount(2, $logs);
        
        // Both should have the same actor (from actor resolution)
        $this->assertEquals($logs[0]->actor_id, $logs[1]->actor_id);
        $this->assertEquals($logs[0]->actor_name, $logs[1]->actor_name);
        $this->assertEquals($logs[0]->actor_role, $logs[1]->actor_role);
        
        // Verify it's using the test user from setUp (system actor)
        $this->assertEquals('Dr. Smith', $logs[0]->actor_name);
        $this->assertEquals('doctor', $logs[0]->actor_role);
    }

    /** @test */
    public function it_provides_all_manual_audit_methods_on_trait()
    {
        $patient = new AuditableFacadeTestPatient();
        $patient->id = 789;
        $patient->first_name = 'Manual';
        $patient->last_name = 'Test';

        // Test all manual audit methods
        $patient->auditCreate();
        $patient->auditRead();
        $patient->auditUpdate();
        $patient->auditDelete();
        $patient->auditExecute('custom_action');

        // Verify all 5 logs were created
        $logs = DB::table('audit_logs')->where('object_id', '789')->get();
        $this->assertCount(5, $logs);
        
        $actions = $logs->pluck('action')->toArray();
        $this->assertContains('C', $actions);
        $this->assertContains('R', $actions);
        $this->assertContains('U', $actions);
        $this->assertContains('D', $actions);
        $this->assertContains('E', $actions);
    }


}

/**
 * Test helper classes
 */
class FacadeTestUser extends Model
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

class FacadeTestPatient extends Model
{
    protected $table = 'patients';
    public $timestamps = false;

    public function getTable()
    {
        return 'patient';
    }

    public function getAuditName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}

class AuditableFacadeTestPatient extends Model
{
    use \CbyteDigital\Nen7513AuditLogging\Traits\Auditable;

    protected $table = 'patients';
    public $timestamps = false;

    public function getTable()
    {
        return 'patient';
    }

    public function getAuditName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
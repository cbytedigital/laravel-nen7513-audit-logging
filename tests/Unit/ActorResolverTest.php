<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Unit;

use CbyteDigital\Nen7513AuditLogging\Services\ActorResolver;
use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

class ActorResolverTest extends TestCase
{
    private ActorResolver $actorResolver;
    private TestActor $testActor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->actorResolver = new ActorResolver($this->app);
        
        $this->testActor = new TestActor();
        $this->testActor->id = 1;
        $this->testActor->name = 'Test User';
        $this->testActor->role = 'test';
    }

    /** @test */
    public function it_resolves_authenticated_user_as_actor()
    {
        // Mock authenticated user
        $this->actingAs($this->testActor);
        
        $actor = $this->actorResolver->resolve();
        
        $this->assertSame($this->testActor, $actor);
    }

    /** @test */
    public function it_returns_system_actor_in_console_context()
    {
        // No authenticated user (default in tests)
        
        // Setup system actor
        $systemActor = new TestActor();
        $systemActor->id = 0;
        $systemActor->name = 'System';
        $systemActor->role = 'system';
        
        $this->app->bind('audit.system_actor', function() use ($systemActor) {
            return $systemActor;
        });

        // Force console context by creating a new resolver with a mocked app
        $mockApp = \Mockery::mock(Application::class);
        $mockApp->shouldReceive('runningInConsole')->andReturn(true);
        $mockApp->shouldReceive('bound')->with('audit.system_actor')->andReturn(true);
        $mockApp->shouldReceive('make')->with('audit.system_actor')->andReturn($systemActor);
        
        $resolver = new ActorResolver($mockApp);
        $actor = $resolver->resolve();
        
        $this->assertSame($systemActor, $actor);
    }

    /** @test */
    public function it_returns_guest_actor_in_web_context()
    {
        // No authenticated user (default in tests)
        
        // Setup guest actor
        $guestActor = new TestActor();
        $guestActor->id = 999;
        $guestActor->name = 'Guest User';
        $guestActor->role = 'guest';
        
        // Force web context by creating a new resolver with a mocked app
        $mockApp = \Mockery::mock(Application::class);
        $mockApp->shouldReceive('runningInConsole')->andReturn(false);
        $mockApp->shouldReceive('bound')->with('audit.guest_actor')->andReturn(true);
        $mockApp->shouldReceive('make')->with('audit.guest_actor')->andReturn($guestActor);
        
        $resolver = new ActorResolver($mockApp);
        $actor = $resolver->resolve();
        
        $this->assertSame($guestActor, $actor);
    }

    /** @test */
    public function it_validates_actor_has_required_methods()
    {
        $this->assertTrue($this->actorResolver->isValidActor($this->testActor));
        
        $invalidActor = new InvalidTestActor();
        $this->assertFalse($this->actorResolver->isValidActor($invalidActor));
    }

    /** @test */
    public function it_throws_exception_for_invalid_actor_missing_name_method()
    {
        $invalidActor = new InvalidTestActor();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement getAuditActorName() method');
        
        $this->actorResolver->validateActor($invalidActor);
    }

    /** @test */
    public function it_throws_exception_for_invalid_actor_missing_role_method()
    {
        $actorWithoutRole = new TestActorWithoutRole();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement getAuditActorRole() method');
        
        $this->actorResolver->validateActor($actorWithoutRole);
    }

    /** @test */
    public function it_uses_provided_actor_when_given()
    {
        $specificActor = new TestActor();
        $specificActor->id = 123;
        $specificActor->name = 'Specific Actor';
        $specificActor->role = 'specific';

        // Use specific actor
        $actor = $this->actorResolver->resolve($specificActor);
        
        $this->assertSame($specificActor, $actor);
        
        // Normal resolution should still work
        $this->actingAs($this->testActor);
        $normalActor = $this->actorResolver->resolve();
        $this->assertSame($this->testActor, $normalActor);
    }

    /** @test */
    public function it_returns_null_when_no_actor_available()
    {
        // Mock console context but no system actor bound
        $mockApp = \Mockery::mock(Application::class);
        $mockApp->shouldReceive('runningInConsole')->andReturn(true);
        $mockApp->shouldReceive('bound')->with('audit.system_actor')->andReturn(false);
        
        $resolver = new ActorResolver($mockApp);
        $actor = $resolver->resolve();
        
        $this->assertNull($actor);
    }

    /** @test */
    public function it_gets_system_actor_when_bound()
    {
        $systemActor = new TestActor();
        $this->app->bind('audit.system_actor', function() use ($systemActor) {
            return $systemActor;
        });

        $result = $this->actorResolver->getSystemActor();
        
        $this->assertSame($systemActor, $result);
    }

    /** @test */
    public function it_returns_null_when_system_actor_not_bound()
    {
        $mockApp = \Mockery::mock(Application::class);
        $mockApp->shouldReceive('bound')->with('audit.system_actor')->andReturn(false);
        
        $resolver = new ActorResolver($mockApp);
        $result = $resolver->getSystemActor();
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_gets_guest_actor_when_bound()
    {
        $guestActor = new TestActor();
        $this->app->bind('audit.guest_actor', function() use ($guestActor) {
            return $guestActor;
        });

        $result = $this->actorResolver->getGuestActor();
        
        $this->assertSame($guestActor, $result);
    }

    /** @test */
    public function it_returns_null_when_guest_actor_not_bound()
    {
        $mockApp = \Mockery::mock(Application::class);
        $mockApp->shouldReceive('bound')->with('audit.guest_actor')->andReturn(false);
        
        $resolver = new ActorResolver($mockApp);
        $result = $resolver->getGuestActor();
        
        $this->assertNull($result);
    }
}

/**
 * Test helper classes
 */
class TestActor extends Model implements \Illuminate\Contracts\Auth\Authenticatable
{
    use \CbyteDigital\Nen7513AuditLogging\Traits\AuditActor;
    use \Illuminate\Auth\Authenticatable;

    protected $table = 'test_actors';
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

class InvalidTestActor extends Model
{
    protected $table = 'invalid_actors';
    public $timestamps = false;
    
    // Missing getAuditActorName() and getAuditActorRole() methods
}

class TestActorWithoutRole extends Model
{
    protected $table = 'actors_without_role';
    public $timestamps = false;
    
    public function getAuditActorName(): string
    {
        return 'Actor Name';
    }
    
    // Missing getAuditActorRole() method
}
<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Unit;

use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use CbyteDigital\Nen7513AuditLogging\Traits\Auditable;
use CbyteDigital\Nen7513AuditLogging\Traits\ParentAuditable;
use CbyteDigital\Nen7513AuditLogging\Services\AuditLogger;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;

class ParentAuditableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test tables
        Schema::create('test_parents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::create('test_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('test_parents');
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }
    
    protected function tearDown(): void
    {
        Schema::dropIfExists('test_children');
        Schema::dropIfExists('test_parents');
        parent::tearDown();
    }

    /** @test */
    public function it_logs_child_creation_on_parent()
    {
        $parent = TestParent::create(['name' => 'Test Parent']);
        
        // Mock the audit logger to capture what gets logged
        $auditLogger = $this->mock(AuditLogger::class);
        $auditLogger->shouldReceive('logUpdate')
            ->once()
            ->with(
                \Mockery::any(), // actor
                'test_parents', // table
                (string) $parent->id, // object_id
                'Test Parent', // object_name
                \Mockery::on(function (AuditContext $context) {
                    $detail = $context->detail;
                    return $detail['child_action'] === 'created' &&
                           $detail['child_type'] === 'TestChild' &&
                           $detail['child_name'] === 'Test Child Title';
                })
            );

        // Create child - should trigger parent audit
        $parent->children()->create([
            'title' => 'Test Child Title',
            'content' => 'Test content'
        ]);
    }


    /** @test */
    public function it_logs_child_updates_with_change_details_on_parent()
    {
        $parent = TestParent::create(['name' => 'Test Parent']);
        $child = $parent->children()->create([
            'title' => 'Original Title',
            'content' => 'Original content'
        ]);

        // Mock the audit logger
        $auditLogger = $this->mock(AuditLogger::class);
        $auditLogger->shouldReceive('logUpdate')
            ->once()
            ->with(
                \Mockery::any(),
                'test_parents',
                (string) $parent->id,
                'Test Parent',
                \Mockery::on(function (AuditContext $context) {
                    $detail = $context->detail;
                    return $detail['child_action'] === 'updated' &&
                           $detail['changed_fields'] === ['title'] &&
                           $detail['old_values']['title'] === 'Original Title' &&
                           $detail['new_values']['title'] === 'Updated Title';
                })
            );

        // Update child - should trigger parent audit with change details
        $child->update(['title' => 'Updated Title']);
    }

    /** @test */
    public function it_allows_custom_parent_audit_actions()
    {
        $parent = TestParent::create(['name' => 'Test Parent']);
        $child = $parent->children()->create([
            'title' => 'Test Child',
            'content' => 'Test content'
        ]);

        // Mock the audit logger
        $auditLogger = $this->mock(AuditLogger::class);
        $auditLogger->shouldReceive('logUpdate')
            ->once()
            ->with(
                \Mockery::any(),
                'test_parents',
                (string) $parent->id,
                'Test Parent',
                \Mockery::on(function (AuditContext $context) {
                    $detail = $context->detail;
                    return $detail['child_action'] === 'custom_action' &&
                           $detail['custom_field'] === 'custom_value';
                })
            );

        // Manual parent audit with custom action
        $child->auditParentAction('custom_action', [
            'custom_field' => 'custom_value'
        ]);
    }

    /** @test */
    public function it_respects_parent_audit_configuration()
    {
        // Test with different configuration
        $parent = TestParent::create(['name' => 'Test Parent']);
        
        // This should not trigger audit because TestChildNoCreate has create disabled
        $auditLogger = $this->mock(AuditLogger::class);
        $auditLogger->shouldNotReceive('logUpdate');

        TestChildNoCreate::create([
            'parent_id' => $parent->id,
            'title' => 'Test',
            'content' => 'Test'
        ]);
    }
}

/**
 * Test Models
 */
class TestParent extends Model
{
    use Auditable;
    
    protected $table = 'test_parents';
    protected $fillable = ['name'];
    
    public function children()
    {
        return $this->hasMany(TestChild::class, 'parent_id');
    }
    
    public function getAuditName(): string
    {
        return $this->name;
    }
}

class TestChild extends Model
{
    use ParentAuditable;
    
    protected $table = 'test_children';
    protected $fillable = ['parent_id', 'title', 'content'];
    
    public function parent()
    {
        return $this->belongsTo(TestParent::class, 'parent_id');
    }
    
    protected static function getParentAuditConfig(): ?array
    {
        return [
            'parent_relation' => 'parent',
            'actions' => [
                'create' => true,
                'update' => true,
                'delete' => true,
            ],
            'context_fields' => [], // All fields
            'child_name' => null, // Auto-detect
        ];
    }
    
    protected function getChildAuditName(): ?string
    {
        return $this->title;
    }
}

class TestChildNoCreate extends Model
{
    use ParentAuditable;
    
    protected $table = 'test_children';
    protected $fillable = ['parent_id', 'title', 'content'];
    
    public function parent()
    {
        return $this->belongsTo(TestParent::class, 'parent_id');
    }
    
    protected static function getParentAuditConfig(): ?array
    {
        return [
            'parent_relation' => 'parent',
            'actions' => [
                'create' => false, // Disabled
                'update' => true,
                'delete' => true,
            ],
        ];
    }
}

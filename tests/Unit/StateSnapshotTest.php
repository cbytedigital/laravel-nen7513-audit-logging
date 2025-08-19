<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Unit;

use CbyteDigital\Nen7513AuditLogging\ValueObjects\StateSnapshot;
use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

class StateSnapshotTest extends TestCase
{

    /** @test */
    public function it_can_capture_model_state()
    {
        // Create a mock model
        $model = new class extends Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'email', 'age'];
            protected $attributes = [
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ];
            
            public function getKey()
            {
                return 123;
            }
            
            public function toArray()
            {
                return [
                    'id' => 123,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'age' => 30,
                ];
            }
        };

        $snapshot = StateSnapshot::capture($model);

        $this->assertEquals(get_class($model), $snapshot->modelClass);
        $this->assertEquals(123, $snapshot->modelId);
        $this->assertEquals([
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ], $snapshot->modelData);
        $this->assertEquals([], $snapshot->relationshipData);
        $this->assertEquals([], $snapshot->trackedRelations);
        $this->assertInstanceOf(\DateTime::class, $snapshot->capturedAt);
    }

    /** @test */
    public function it_can_compare_model_changes()
    {
        // Create a mock model
        $model = new class extends Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'email', 'age'];
            protected $attributes = [
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ];
            
            public function getKey()
            {
                return 123;
            }
            
            public function toArray()
            {
                return $this->attributes;
            }
            
            public function updateData(array $changes)
            {
                $this->attributes = array_merge($this->attributes, $changes);
            }
        };

        // Capture initial state
        $snapshot = StateSnapshot::capture($model);

        // Make changes
        $model->updateData([
            'name' => 'Jane Doe',
            'age' => 31,
        ]);

        // Compare
        $comparison = $snapshot->compareWith($model);

        $this->assertTrue($comparison['has_changes']);
        $this->assertEquals([
            'name' => ['old' => 'John Doe', 'new' => 'Jane Doe'],
            'age' => ['old' => 30, 'new' => 31],
        ], $comparison['model_changes']);
        $this->assertEquals([], $comparison['relationship_changes']);
    }

    /** @test */
    public function it_detects_no_changes_when_model_is_unchanged()
    {
        $model = new class extends Model {
            protected $table = 'test_models';
            protected $attributes = ['id' => 123, 'name' => 'John Doe'];
            
            public function getKey()
            {
                return 123;
            }
            
            public function toArray()
            {
                return ['id' => 123, 'name' => 'John Doe'];
            }
        };

        $snapshot = StateSnapshot::capture($model);
        $comparison = $snapshot->compareWith($model);

        $this->assertFalse($comparison['has_changes']);
        $this->assertEquals([], $comparison['model_changes']);
        $this->assertEquals([], $comparison['relationship_changes']);
    }
}

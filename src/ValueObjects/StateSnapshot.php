<?php

namespace CbyteDigital\Nen7513AuditLogging\ValueObjects;

use Illuminate\Database\Eloquent\Model;

/**
 * StateSnapshot - Captures the state of a model and its relationships
 * 
 * This immutable value object stores a snapshot of a model's state
 * including specified relationships for later comparison.
 */
readonly class StateSnapshot
{
    public function __construct(
        public string $modelClass,
        public string|int $modelId,
        public array $modelData,
        public array $relationshipData,
        public array $trackedRelations,
        public \DateTime $capturedAt
    ) {}

    /**
     * Create a snapshot from a model and specified relations
     */
    public static function capture(Model $model, array $relations = []): self
    {
        // Capture relationship data first
        $relationshipData = [];
        foreach ($relations as $relation) {
            if (method_exists($model, $relation)) {
                // Load the relation if not already loaded
                if (!$model->relationLoaded($relation)) {
                    $model->load($relation);
                }
                
                $relationData = $model->$relation;
                $relationshipData[$relation] = $relationData instanceof \Illuminate\Database\Eloquent\Collection 
                    ? $relationData->toArray() 
                    : ($relationData ? $relationData->toArray() : null);
            }
        }
        
        // Get model data WITHOUT relationships
        $modelData = $model->attributesToArray(); // Use attributesToArray() instead of toArray()
        
        return new self(
            modelClass: get_class($model),
            modelId: $model->getKey(),
            modelData: $modelData,
            relationshipData: $relationshipData,
            trackedRelations: $relations,
            capturedAt: new \DateTime()
        );
    }

    /**
     * Compare this snapshot with the current state of a model
     */
    public function compareWith(Model $model): array
    {
        // Verify we're comparing the same model
        if (get_class($model) !== $this->modelClass || $model->getKey() !== $this->modelId) {
            throw new \InvalidArgumentException('Cannot compare snapshot with different model');
        }

        $changes = [
            'model_changes' => [],
            'relationship_changes' => [],
            'has_changes' => false,
        ];

        // Compare model data (attributes only, no relationships)
        $currentModelData = $model->attributesToArray();
        $modelChanges = $this->compareArrays($this->modelData, $currentModelData);
        
        if (!empty($modelChanges)) {
            $changes['model_changes'] = $modelChanges;
            $changes['has_changes'] = true;
        }

        // Compare relationships
        foreach ($this->trackedRelations as $relation) {
            if (method_exists($model, $relation)) {
                // FORCE reload the relation to get fresh data from database
                $model->load($relation);
                
                $currentRelationData = $model->$relation;
                $currentArray = $currentRelationData instanceof \Illuminate\Database\Eloquent\Collection 
                    ? $currentRelationData->toArray() 
                    : ($currentRelationData ? $currentRelationData->toArray() : null);
                
                $originalArray = $this->relationshipData[$relation] ?? null;
                
                $relationChanges = $this->compareRelationArrays($originalArray, $currentArray, $relation);
                
                if (!empty($relationChanges)) {
                    $changes['relationship_changes'][$relation] = $relationChanges;
                    $changes['has_changes'] = true;
                }
            }
        }

        return $changes;
    }

    /**
     * Compare two arrays and return changes
     */
    private function compareArrays(array $original, array $current): array
    {
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($original), array_keys($current)));
        
        foreach ($allKeys as $key) {
            $oldValue = $original[$key] ?? null;
            $newValue = $current[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Compare relationship arrays (collections)
     */
    private function compareRelationArrays(?array $original, ?array $current, string $relationName): array
    {
        $original = $original ?? [];
        $current = $current ?? [];
        
        // For collections, compare by ID
        if ($this->isIndexedArray($original) && $this->isIndexedArray($current)) {
            return $this->compareCollections($original, $current, $relationName);
        }
        
        // For single models, compare directly
        return $this->compareArrays($original, $current);
    }

    /**
     * Compare two collections of models
     */
    private function compareCollections(array $original, array $current, string $relationName): array
    {
        $originalIds = collect($original)->pluck('id')->filter()->sort()->values()->toArray();
        $currentIds = collect($current)->pluck('id')->filter()->sort()->values()->toArray();
        
        $added = array_diff($currentIds, $originalIds);
        $removed = array_diff($originalIds, $currentIds);
        $common = array_intersect($originalIds, $currentIds);
        
        $changes = [
            'collection_summary' => [
                'original_count' => count($original),
                'current_count' => count($current),
                'added_ids' => array_values($added),
                'removed_ids' => array_values($removed),
                'modified_ids' => [],
            ],
            'added_records' => [],
            'removed_records' => [],
            'modified_records' => [],
        ];
        
        // Get added records
        foreach ($current as $record) {
            if (isset($record['id']) && in_array($record['id'], $added)) {
                $changes['added_records'][] = $record;
            }
        }
        
        // Get removed records
        foreach ($original as $record) {
            if (isset($record['id']) && in_array($record['id'], $removed)) {
                $changes['removed_records'][] = $record;
            }
        }
        
        // Check for modifications in common records
        $originalById = collect($original)->keyBy('id');
        $currentById = collect($current)->keyBy('id');
        
        foreach ($common as $id) {
            $originalRecord = $originalById[$id] ?? [];
            $currentRecord = $currentById[$id] ?? [];
            
            $recordChanges = $this->compareArrays($originalRecord, $currentRecord);
            if (!empty($recordChanges)) {
                $changes['modified_records'][] = [
                    'id' => $id,
                    'changes' => $recordChanges,
                ];
                $changes['collection_summary']['modified_ids'][] = $id;
            }
        }
        
        // Only return changes if there are actual changes
        $hasChanges = !empty($changes['added_records']) || 
                     !empty($changes['removed_records']) || 
                     !empty($changes['modified_records']);
        
        return $hasChanges ? $changes : [];
    }

    /**
     * Check if array is indexed (collection) vs associative (single model)
     */
    private function isIndexedArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }
        
        return array_keys($array) === range(0, count($array) - 1);
    }
}

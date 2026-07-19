<?php

namespace App\Support\Traits;

use App\Support\IdGenerators\IdGenerator;
use Illuminate\Database\Eloquent\Model;

/**
 * HasDualId trait — supports both UUID v7 and bigint IDs.
 *
 * Usage in models:
 *
 *   class Entry extends Model {
 *       use HasDualId;
 *       // Optionally override: protected $idType = 'bigint';
 *   }
 *
 * The trait auto-detects the ID type from:
 *   1. $idType property on the model
 *   2. cms.id_type config (default: uuid_v7)
 *
 * The trait handles:
 *   - Auto-generating IDs on create
 *   - Proper key type casting ($keyType = 'string' for UUID, 'int' for bigint)
 *   - Disabling incrementing for UUID
 *   - Route model binding for both types
 */
trait HasDualId
{
    public static function bootHasDualId(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = IdGenerator::generate(static::class);
            }
        });
    }

    public function getKeyType(): string
    {
        return static::getIdType() === 'bigint' ? 'int' : 'string';
    }

    public function getIncrementing(): bool
    {
        return static::getIdType() === 'bigint';
    }

    /**
     * Get the ID type for this model.
     * Override in model with: protected static function getIdType(): string { return 'bigint'; }
     */
    public static function getIdType(): string
    {
        $instance = new static();
        return $instance->idType ?? config('cms.id_type', 'uuid_v7');
    }

    /**
     * Resolve route binding — supports both UUID and bigint lookups.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        // Auto-detect ID type and cast appropriately
        if (IdGenerator::isUuid((string)$value)) {
            $value = (string) $value;
        } elseif (IdGenerator::isBigint($value) && static::getIdType() === 'bigint') {
            $value = (int) $value;
        }

        return $this->where($field, $value)->first();
    }

    /**
     * Resolve child route binding.
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        $child = $this->{Str::plural(Str::camel($childType))}();
        return $child->where($field, $value)->first();
    }
}

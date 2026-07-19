<?php

namespace App\Support\IdGenerators;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * ID Generator — supports both UUID v7 (time-ordered, default) and bigint.
 *
 * UUID v7 is preferred for:
 * - Distributed systems (no central ID authority needed)
 * - Time-ordered (better B-tree index locality than UUID v4)
 * - Globally unique (safe for sharding)
 *
 * BigInt is preferred for:
 * - High-write single-database systems
 * - Smaller storage (8 bytes vs 16 bytes)
 * - Traditional Laravel compatibility
 *
 * Configure via CMS_ID_TYPE=uuid_v7 or CMS_ID_TYPE=bigint in .env
 */
class IdGenerator
{
    public static function generate(?string $modelClass = null): string|int
    {
        $type = self::getType($modelClass);

        return match ($type) {
            'bigint' => self::generateBigint(),
            'uuid_v7' => self::generateUuidV7(),
            'uuid_v4' => self::generateUuidV4(),
            default => self::generateUuidV7(),
        };
    }

    public static function generateUuidV7(): string
    {
        // UUID v7: 48-bit Unix timestamp + 12-bit rand_a + 62-bit rand_b
        // Time-ordered, sortable, better index performance than v4
        if (function_exists('uuid_create')) {
            return uuid_create(\UUID_TYPE_TIME); // If ext-uuid is installed
        }

        // Fallback: manual UUID v7 generation
        $timestamp = intval(microtime(true) * 1000);
        $timestampHex = str_pad(dechex($timestamp), 12, '0', STR_PAD_LEFT);

        $randA = bin2hex(random_bytes(8)); // 16 hex chars
        $randB = bin2hex(random_bytes(8));

        // Version 7 + variant bits
        $versioned = substr($randA, 0, 3) . '7' . substr($randA, 4);
        $variant = dechex((hexdec(substr($randB, 0, 2)) & 0x3f) | 0x80) . substr($randB, 2);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($timestampHex, 0, 8),
            substr($timestampHex, 8, 4),
            $versioned,
            $variant,
            substr($randB, 4)
        );
    }

    public static function generateUuidV4(): string
    {
        return Str::uuid()->toString();
    }

    public static function generateBigint(): int
    {
        // Snowflake-like ID: 41-bit timestamp + 10-bit machine + 12-bit sequence
        $timestamp = intval(microtime(true) * 1000) - 1700000000000; // Epoch offset
        $machineId = Config::get('cms.machine_id', 1) & 0x3FF;
        $sequence = random_int(0, 0xFFF);

        return ($timestamp << 22) | ($machineId << 12) | $sequence;
    }

    public static function getType(?string $modelClass = null): string
    {
        // Per-model override
        if ($modelClass && method_exists($modelClass, 'getIdType')) {
            return $modelClass::getIdType();
        }

        return Config::get('cms.id_type', 'uuid_v7');
    }

    public static function isUuid(string $id): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id);
    }

    public static function isBigint(string|int $id): bool
    {
        return is_numeric($id) && !str_contains((string)$id, '-');
    }

    public static function normalize(string|int $id): string|int
    {
        return self::isUuid((string)$id) ? (string)$id : (int)$id;
    }
}

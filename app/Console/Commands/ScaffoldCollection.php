<?php

namespace App\Console\Commands;

use App\Models\Tenant\Collection;
use App\Models\Tenant\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ScaffoldCollection extends Command
{
    protected $signature = 'scaffold:collection {name} {handle?} {--blueprint=default}';
    protected $description = 'Scaffold a new collection with a default blueprint.';

    public function handle(): int
    {
        if (! tenancy()->initialized) {
            $this->error('This command must be run within tenant context.');
            return self::FAILURE;
        }

        $name = $this->argument('name');
        $handle = $this->argument('handle') ?? Str::slug($name);
        $blueprintHandle = $this->option('blueprint');

        $blueprint = Blueprint::where('tenant_id', tenant('id'))
            ->where('handle', $blueprintHandle)
            ->first();

        if (! $blueprint) {
            $this->error("Blueprint not found: {$blueprintHandle}");
            return self::FAILURE;
        }

        $collection = Collection::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'name' => $name,
            'handle' => $handle,
            'route_pattern' => "/{slug}",
            'template' => 'default',
            'structure_mode' => 'flat',
            'default_status' => 'draft',
            'is_searchable' => true,
        ]);

        $collection->blueprints()->attach($blueprint->id, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'is_primary' => true,
        ]);

        $this->info("✓ Created collection '{$name}' (handle: {$handle})");
        $this->info("  Blueprint: {$blueprint->handle}");
        $this->info("  Route pattern: /{slug}");

        return self::SUCCESS;
    }
}

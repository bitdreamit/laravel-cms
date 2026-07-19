<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;

class AddTag implements WorkflowActionInterface
{
    public function execute(WorkflowInstance $instance, array $config): array
    {
        $entry = \App\Models\Tenant\Entry::find($instance->entry_id);

        if (! $entry) {
            return ['error' => 'Entry not found'];
        }

        $taxonomyHandle = $config['taxonomy'] ?? null;
        $termSlug = $config['term'] ?? null;

        if (! $taxonomyHandle || ! $termSlug) {
            return ['error' => 'Taxonomy and term are required'];
        }

        // Find or create the term
        $taxonomy = \App\Models\Tenant\Taxonomy::where('handle', $taxonomyHandle)->first();
        if (! $taxonomy) {
            return ['error' => "Taxonomy not found: {$taxonomyHandle}"];
        }

        $term = \App\Models\Tenant\Term::firstOrCreate([
            'tenant_id' => $instance->tenant_id,
            'taxonomy_id' => $taxonomy->id,
            'slug' => $termSlug,
        ], [
            'title' => str_replace('-', ' ', ucwords($termSlug, '-')),
        ]);

        \App\Models\Tenant\EntryTerm::firstOrCreate([
            'tenant_id' => $instance->tenant_id,
            'entry_id' => $entry->id,
            'term_id' => $term->id,
        ]);

        return ['term_id' => $term->id, 'term' => $term->slug];
    }
}

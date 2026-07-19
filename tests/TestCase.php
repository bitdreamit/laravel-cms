<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure central + tenant migrations
        $this->app->make('migrator')->path(database_path('migrations/central'));
        $this->app->make('migrator')->path(database_path('migrations/tenant'));
    }
}

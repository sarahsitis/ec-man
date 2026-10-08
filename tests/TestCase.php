<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // Check before RefreshDatabase can run migrations or delete any data.
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}");
        if (!$app->environment('testing') || $connection !== 'sqlite'
            || ($database['database'] ?? null) !== ':memory:'
            || !empty($database['url'])) {
            throw new \RuntimeException('Tests require the isolated SQLite :memory: database.');
        }

        return $app;
    }
}

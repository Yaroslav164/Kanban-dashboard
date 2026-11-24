<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $database = $app->make('db')->connection();

        if (! $app->environment('testing') || $database->getDriverName() !== 'pgsql' || $database->getDatabaseName() !== 'kanban_testing') {
            throw new LogicException('Feature tests require the dedicated PostgreSQL database kanban_testing.');
        }

        return $app;
    }
}

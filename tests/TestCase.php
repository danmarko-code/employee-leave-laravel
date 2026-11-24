<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $devDatabase = $this->developmentDatabaseName();

        $this->abortIfDevelopmentDatabase((string) (getenv('DB_DATABASE') ?: ''), $devDatabase);

        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $activeDatabase = $app['config']->get("database.connections.{$connection}.database");

        $this->abortIfDevelopmentDatabase($activeDatabase, $devDatabase);

        return $app;
    }

    private function developmentDatabaseName(): ?string
    {
        $path = dirname(__DIR__, 2) . '/.env';

        if (! is_file($path)) {
            return null;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = ltrim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

            if (trim($key) === 'DB_DATABASE') {
                return trim(trim($value), "\"'");
            }
        }

        return null;
    }

    private function abortIfDevelopmentDatabase(?string $activeDatabase, ?string $devDatabase): void
    {
        if ($devDatabase === null || $devDatabase === '' || $activeDatabase === null || $activeDatabase === '') {
            return;
        }

        if ($activeDatabase === $devDatabase) {
            throw new RuntimeException(
                'Test run aborted: tests must not use the development database from .env. Use SQLite in memory.'
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace EndeavourAgency\LaravelMigrationTests\Traits;

use Artisan;
use Closure;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\Concerns\InteractsWithTestCaseLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

trait TestsLaravelMigrationsTrait
{
    use InteractsWithTestCaseLifecycle;

    protected string $migrationUnderTest;

    protected function testMigration(
        string $migrationUnderTest,
        Closure $setup,
        Closure $tests,
    ): void {
        // Reset database
        $this
            ->migrationUnderTest($migrationUnderTest)
            ->wipeDb();

        // Migrate database up til the point of the migration under test
        $migrationsBefore = $this->collectMigrationsBefore();
        $this->getMigrator()->runPending($migrationsBefore);

        // Run user specified setup work
        $setup();

        // Run the migration under test
        $migrationUnderTest = $this->collectMigrationUnderTest();
        $this->getMigrator()->runPending([$migrationUnderTest]);

        // Run user specified tests
        $tests();

        // Set migration status to false, so that any subsequent tests
        // will automatically migrate the database to the latest state
        RefreshDatabaseState::$migrated = false;
    }

    protected function getMigrator(): Migrator
    {
        return $this->app->make('migrator');
    }

    protected function migrationUnderTest(string $migrationUnderTest): self
    {
        $this->migrationUnderTest = $migrationUnderTest;

        return $this;
    }

    protected function wipeDb(): self
    {
        Artisan::call('db:wipe');
        Artisan::call('migrate:install');

        return $this;
    }

    protected function getMigrationFiles(): array
    {
        $migrator             = $this->getMigrator();
        $migrationDirectories = array_merge($migrator->paths(), [database_path('migrations')]);

        return $migrator->getMigrationFiles($migrationDirectories);
    }

    protected function collectMigrationsBefore(): array
    {
        $migrationUnderTestFound = false;

        return $this->filterMigrationFiles(
            $this->getMigrationFiles(),
            function ($migrationName) use (&$migrationUnderTestFound): bool {
                if (
                    $migrationUnderTestFound
                    || $migrationName === $this->migrationUnderTest
                ) {
                    $migrationUnderTestFound = true;

                    return false;
                }

                return true;
            }
        );
    }

    protected function collectMigrationUnderTest(): string
    {
        $foundMigration = $this->filterMigrationFiles(
            $this->getMigrationFiles(),
            function ($migrationName): bool {
                return $migrationName === $this->migrationUnderTest;
            }
        );

        return reset($foundMigration);
    }

    /**
     * @param array<string, string> $migrationFiles
     * @param Closure(string $migrationName, string $migrationFile): bool $filterMethod
     * @return array
     */
    protected function filterMigrationFiles(
        array $migrationFiles,
        Closure $filterMethod,
    ): array {
        $filtered = [];

        foreach ($migrationFiles as $migrationName => $migrationFile) {
            if ($filterMethod($migrationName, $migrationFile)) {
                $filtered[$migrationName] = $migrationFile;
            }
        }

        return $filtered;
    }
}

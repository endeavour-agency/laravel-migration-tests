<?php

declare(strict_types=1);

namespace EndeavourAgency\LaravelMigrationTests\Traits;

use Closure;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use InvalidArgumentException;

trait TestsLaravelMigrationsTrait
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /** @var array<string, string> */
    protected array $migrationFiles;

    /** @var array<string, string> */
    protected array $foundMigrationFiles = [];

    protected function testMigration(
        string $migration,
        Closure | null $setup = null,
        Closure | null $tests = null,
    ): void {
        // Migrate database up til the point of the migration under test
        $this->runMigrationsBefore($migration);

        // Run user specified setup work
        if ($setup !== null) {
            $setup();
        }

        // Run the migration under test
        $this->runMigration($migration);

        // Run user specified tests
        if ($tests !== null) {
            $tests();
        }
    }

    protected function wipeDatabase(): self
    {
        $this->artisan('db:wipe');
        $this->artisan('migrate:install');

        return $this;
    }

    /**
     * @return array<string, string>
     */
    protected function getMigrationFiles(): array
    {
        if (isset($this->migrationFiles)) {
            return $this->migrationFiles;
        }

        $migrator             = $this->getMigrator();
        $migrationDirectories = array_merge($migrator->paths(), [database_path('migrations')]);

        return $this->migrationFiles = $migrator->getMigrationFiles($migrationDirectories);
    }

    protected function runNextMigration(int $amount = 1): self
    {
        $migrationFiles = $this->getMigrationFiles();
        $ran            = $this->getMigrator()->getRepository()->getRan();
        $notRan         = array_diff(array_keys($migrationFiles), $ran);

        if (empty($notRan)) {
            return $this;
        }

        $migrationsToRun = array_slice($notRan, 0, $amount);

        foreach ($migrationsToRun as $migration) {
            $this->runMigration($migration);
        }

        return $this;
    }

    protected function runMigration(string $migration): self
    {
        $migrationUnderTest = $this->findMigrationFile($migration);
        $this->getMigrator()->runPending([$migrationUnderTest]);

        return $this;
    }

    protected function runMigrationsBefore(string $migration): self
    {
        $migrations = $this->collectMigrationsBefore($migration);

        // Reset database
        $this->wipeDatabase();

        $this->getMigrator()->runPending($migrations);

        $this->resetDatabaseMigrationState();

        return $this;
    }

    /**
     * @param string $migration
     * @return array<string, string>
     */
    protected function collectMigrationsBefore(string $migration): array
    {
        $migration = $this->findMigrationFile($migration);

        $migrationsBefore = [];

        foreach ($this->getMigrationFiles() as $migrationName => $migrationFile) {
            if ($migrationFile === $migration) {
                break;
            }

            $migrationsBefore[$migrationName] = $migrationFile;
        }

        return $migrationsBefore;
    }

    protected function findMigrationFile(string $migration): string | null
    {
        $cacheKey = $migration;

        // First check if we have already searched for this migration
        if (array_key_exists($cacheKey, $this->foundMigrationFiles)) {
            return $this->foundMigrationFiles[$cacheKey];
        }

        $migrationFiles = $this->getMigrationFiles();

        // Provided migration might be a file name
        if (str_ends_with($migration, '.php')) {
            $migration = array_search($migration, $this->getMigrationFiles());
        }

        if (array_key_exists($migration, $migrationFiles)) {
            return $this->foundMigrationFiles[$cacheKey] = $migrationFiles[$migration];
        }

        throw new InvalidArgumentException('Could not find migration file ' . $cacheKey);
    }

    protected function getMigrator(): Migrator
    {
        return $this->app->make('migrator');
    }

    /**
     * Set migration status to false, so that any subsequent tests
     * will automatically migrate the database to the latest state
     */
    protected function resetDatabaseMigrationState(): self
    {
        RefreshDatabaseState::$migrated = false;

        return $this;
    }
}

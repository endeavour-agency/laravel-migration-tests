<?php

declare(strict_types=1);

namespace Tests\Integration;

use EndeavourAgency\LaravelMigrationTests\Traits\TestsLaravelMigrationsTrait;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mockery;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TestsLaravelMigrationsTraitTest extends TestCase
{
    use WithWorkbench;
    use TestsLaravelMigrationsTrait;

    #[Test]
    public function it_performs_setup_work_and_then_performs_assertions(): void
    {
        $this->testMigration(
            '2024_11_22_110000_rename_name_to_title_on_books_table',
            tests: function () {
                static::assertDatabaseHas('books', [
                    'id'     => 15,
                    'title'  => 'Harry Potter and the Half-Blood Prince',
                    'author' => 'J.K. Rowling',
                ]);
            },
            setup: function () {
                DB::table('books')->insert([
                    'id'     => 15,
                    'name'   => 'Harry Potter and the Half-Blood Prince',
                    'author' => 'J.K. Rowling',
                ]);

                static::assertFalse(RefreshDatabaseState::$migrated);
            }
        );
    }

    #[Test]
    public function it_performs_assertions_without_doing_setup(): void
    {
        $this->testMigration(
            '2024_11_22_110000_rename_name_to_title_on_books_table',
            tests: function () {
                $lastMigration = DB::table('migrations')
                    ->select('migration')
                    ->orderByDesc('id')
                    ->first()
                    ->migration;

                static::assertSame('2024_11_22_110000_rename_name_to_title_on_books_table', $lastMigration);
            },
        );
    }

    #[Test]
    public function it_tests_a_migration_provided_as_a_file(): void
    {
        $migration = dirname(__DIR__) . '/resources/migrations/2024_11_22_110000_rename_name_to_title_on_books_table.php';

        $this->testMigration(
            $migration,
            tests: function () {
                $lastMigration = DB::table('migrations')
                    ->select('migration')
                    ->orderByDesc('id')
                    ->first()
                    ->migration;

                static::assertSame('2024_11_22_110000_rename_name_to_title_on_books_table', $lastMigration);
            },
        );
    }

    #[Test]
    public function it_throws_an_exception_if_it_can_not_find_provided_migration(): void
    {
        $this->app->instance(
            ConsoleKernelContract::class,
            $kernel = Mockery::spy(ConsoleKernel::class),
        );

        $kernel
            ->shouldNotReceive('call')
            ->with('db:wipe');

        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('Could not find migration file 2024_11_23_110000_create_foo_bars_table');

        $this->testMigration(
            '2024_11_23_110000_create_foo_bars_table',
            tests: function () {
                static::fail('Test function should not be executed');
            },
        );
    }
}

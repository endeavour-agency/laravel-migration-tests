# Laravel Migration Tests
This package provides a trait that can be used to test migrations. It allows you to specify which migration
you wish to test. It will then create a fresh database, run any migrations up until the specified migration,
let you do setup work, run the migration under test, and then let you do your assertions.

## Example

```php
<?php

declare(strict_types=1);

namespace Tests\Migrations;

use EndeavourAgency\LaravelMigrationTests\Traits\TestsLaravelMigrationsTrait;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExampleMigrationTest extends TestCase
{
    use TestsLaravelMigrationsTrait;

    #[Test]
    public function it_tests_a_migration_using_the_test_migration_method(): void
    {
        $this->testMigration(
            '2024_05_29_083854_rename_birth_day_column_to_date_of_birth_on_users_table',
            setup: function () {
                // Setup
                DB::table('users')->insert([
                    'id'        => 15,
                    'email'     => 'foo@bar.com',
                    'birth_day' => '1990-02-01',
                ]);
            },
            tests: function () {
                // Tests
                static::assertDatabaseHas('users', [
                    'id'            => 15,
                    'date_of_birth' => '1990-02-01',
                ]);
            }
        );
    }
    
    #[Test]
    public function it_tests_a_migration_using_convenience_methods(): void
    {
        $this->runMigrationsBefore('2024_05_29_083854_rename_birth_day_column_to_date_of_birth_on_users_table');
        
        // Setup
        DB::table('users')->insert([
            'id'        => 15,
            'email'     => 'foo@bar.com',
            'birth_day' => '1990-02-01',
        ]);
        
        $this->runNextMigration();
        
        // Tests
        static::assertDatabaseHas('users', [
            'id'            => 15,
            'date_of_birth' => '1990-02-01',
        ]);
    }
    
}
```

## Getting started
To get started, simply install the package.
```shell
composer require --dev endeavour-agency/laravel-migration-tests
```

Then use `EndeavourAgency\LaravelMigrationTests\Traits\TestsLaravelMigrationsTrait` in your test class.

## Important notes
Testing migrations can be extremely helpful when you are doing complicated database data or structure mutations. It also
makes it possible to write your migrations in a test-driven way. 
However, it is advisable to delete migration tests from your test suite after the migrations have run on production, as 
these tests are slow of nature. This is because they need to tear down your entire database before it can run its assertions.

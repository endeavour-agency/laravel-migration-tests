# Laravel Migration Tests
This package provides a trait that can be used to test migrations. It allows you to specify which migration
you wish to test. It will then create a fresh database, run any migrations up until the specified migration,
let you do setup work, run the migration under test, and then let you do your assertions.

## Example

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Migrations;

use EndeavourAgency\LaravelMigrationTests\Traits\TestsLaravelMigrationsTrait;use Illuminate\Foundation\Testing\TestCase;use PHPUnit\Framework\Attributes\Test;

class ExampleMigrationTest extends TestCase
{
    use TestsLaravelMigrationsTrait;

    #[Test]
    public function it_tests_a_migration(): void
    {
        $this->testMigration(
            '2024_05_29_083854_rename_birth_day_column_to_date_of_birth_on_users_table',
            function () {
                // Setup
                DB::table('users')->insert([
                    'id'        => 15,
                    'email'     => 'foo@bar.com',
                    'birth_day' => '1990-02-01',
                ]);
            },
            function () {
                // Tests
                static::assertDatabaseHas('users', [
                    'id'            => 15,
                    'date_of_birth' => '1990-02-01',
                ]);
            }
        );
    }
}
```

## Getting started
To get started, simply install the package.
```shell
composer require --dev endeavour-agency/laravel-migration-tests
```

Then use `EndeavourAgency\LaravelMigrationTests\Traits\TestsLaravelMigrationsTrait` in your test class.

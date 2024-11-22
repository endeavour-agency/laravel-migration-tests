<?php

declare(strict_types=1);

namespace Tests\Providers;

use Illuminate\Support\ServiceProvider;

class LaravelMigrationsTestsTestProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            dirname(__DIR__) . '/resources/migrations',
        ]);
    }
}

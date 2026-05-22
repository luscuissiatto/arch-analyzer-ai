<?php

namespace App\Providers;

use App\Domain\Ports\DiagramQueuePort;
use App\Domain\Ports\DiagramRepositoryPort;
use App\Infrastructure\Adapters\EloquentDiagramRepositoryAdapter;
use App\Infrastructure\Adapters\RabbitMQDiagramAdapter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DiagramQueuePort::class, RabbitMQDiagramAdapter::class);
        $this->app->bind(DiagramRepositoryPort::class, EloquentDiagramRepositoryAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

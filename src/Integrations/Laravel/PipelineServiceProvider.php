<?php

namespace Troum\Pipeline\Integrations\Laravel;

use Illuminate\Support\ServiceProvider;
use Troum\Pipeline\Core\Pipeline;

class PipelineServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Pipeline::class, function ($app) {
            return new LaravelPipeline($app);
        });
    }
}

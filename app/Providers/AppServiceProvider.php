<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use L5Swagger\GeneratorFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->beforeResolving(GeneratorFactory::class, function (): void {
            config(['l5-swagger.defaults.scanOptions.analyser' => new ReflectionAnalyser([
                new DocBlockAnnotationFactory,
            ])]);
        });
    }
}

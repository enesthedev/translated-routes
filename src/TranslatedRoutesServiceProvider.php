<?php

namespace Enes\TranslatedRoutes;

use Enes\TranslatedRoutes\Commands\ClearTranslatedRoutesCache;
use Enes\TranslatedRoutes\Commands\ExportTranslatedRoutes;
use Enes\TranslatedRoutes\Commands\ListTranslatedRoutes;
use Enes\TranslatedRoutes\Commands\ProfileTranslatedRoutes;
use Enes\TranslatedRoutes\Commands\TranslatedRoutesCommand;
use Enes\TranslatedRoutes\Commands\ValidateTranslatedRoutes;
use Enes\TranslatedRoutes\Middleware\ShareInertiaData;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\ServiceProvider;

class TranslatedRoutesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translated-routes.php', 'translated-routes');

        $this->app->singleton(TranslatedRoutes::class, function () {
            return new TranslatedRoutes;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/translated-routes.php' => config_path('translated-routes.php'),
            ], 'translated-routes-config');

            $this->commands([
                TranslatedRoutesCommand::class,
                ClearTranslatedRoutesCache::class,
                ValidateTranslatedRoutes::class,
                ListTranslatedRoutes::class,
                ExportTranslatedRoutes::class,
                ProfileTranslatedRoutes::class,
            ]);
        }

        $this->registerMiddleware();
        $this->registerRouteMacros();
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('share-inertia-locale', ShareInertiaData::class);
    }

    protected function registerRouteMacros(): void
    {
        // Route translate macro for individual routes
        Route::macro('translate', function () {
            $route = $this;
            $originalUri = $route->uri();

            $translatedUri = app(TranslatedRoutes::class)->translate($originalUri);

            $route->setUri($translatedUri);

            return $route;
        });

        // RouteRegistrar translate macro for group chaining
        // This creates a wrapper that will translate routes when group() is called
        RouteRegistrar::macro('translate', function () {
            $registrar = $this;
            
            // Return a proxy that intercepts the group() call
            return new class($registrar) {
                private $registrar;
                private $router;
                
                public function __construct($registrar) {
                    $this->registrar = $registrar;
                    $this->router = app('router');
                }
                
                public function group(\Closure $callback) {
                    // Get route count before group registration
                    $beforeCount = count($this->router->getRoutes()->getRoutes());
                    
                    // Call the actual group method
                    $this->registrar->group($callback);
                    
                    // Translate all newly added routes
                    $allRoutes = $this->router->getRoutes()->getRoutes();
                    $newRoutes = array_slice($allRoutes, $beforeCount);
                    
                    foreach ($newRoutes as $route) {
                        $originalUri = $route->uri();
                        $translatedUri = app(\Enes\TranslatedRoutes\TranslatedRoutes::class)->translate($originalUri);
                        $route->setUri($translatedUri);
                    }
                }
                
                // Forward any other method calls to the registrar
                public function __call($method, $parameters) {
                    return $this->registrar->$method(...$parameters);
                }
            };
        });

        // Router translateGroup macro for translating all routes in a group
        RouteFacade::macro('translateGroup', function (array $attributes, \Closure $callback) {
            $router = app('router');

            // Get count of routes before adding new ones
            $beforeCount = count($router->getRoutes()->getRoutes());
            // Register the group
            RouteFacade::group($attributes, $callback);

            // Get all routes and translate the newly added ones
            $allRoutes = $router->getRoutes()->getRoutes();
            $newRoutes = array_slice($allRoutes, $beforeCount);

            foreach ($newRoutes as $route) {
                $originalUri = $route->uri();
                $translatedUri = app(TranslatedRoutes::class)->translate($originalUri);
                $route->setUri($translatedUri);
            }
        });
    }
}

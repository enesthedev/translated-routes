<?php

namespace Enes\TranslatedRoutes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Enes\TranslatedRoutes\Facades\TranslatedRoutes;

class ListTranslatedRoutes extends Command
{
    public $signature = 'translated-routes:list {--locale= : Show routes for specific locale}';

    public $description = 'List all translated routes';

    public function handle(): int
    {
        $locale = $this->option('locale');
        $locales = $locale ? [$locale] : array_keys(TranslatedRoutes::getSupportedLocales());

        $routeCollection = Route::getRoutes();
        $translatedRoutes = [];

        foreach ($routeCollection->getRoutes() as $route) {
            $uri = $route->uri();
            $name = $route->getName();
            $methods = implode('|', $route->methods());

            if ($uri === '/') {
                continue;
            }

            foreach ($locales as $loc) {
                $translatedUri = TranslatedRoutes::translate($uri, $loc);
                
                $translatedRoutes[] = [
                    'method' => $methods,
                    'uri' => $translatedUri,
                    'name' => $name ?? '-',
                    'locale' => $loc,
                ];
            }
        }

        if (empty($translatedRoutes)) {
            $this->info('No translated routes found.');
            return self::SUCCESS;
        }

        // Sort by locale and URI
        usort($translatedRoutes, function ($a, $b) {
            if ($a['locale'] === $b['locale']) {
                return strcmp($a['uri'], $b['uri']);
            }
            return strcmp($a['locale'], $b['locale']);
        });

        $this->table(
            ['Method', 'URI', 'Name', 'Locale'],
            array_map(fn($route) => [
                $route['method'],
                $route['uri'],
                $route['name'],
                $route['locale'],
            ], $translatedRoutes)
        );

        return self::SUCCESS;
    }
}


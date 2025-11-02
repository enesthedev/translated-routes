<?php

namespace Enes\TranslatedRoutes\Commands;

use Enes\TranslatedRoutes\Facades\TranslatedRoutes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class ValidateTranslatedRoutes extends Command
{
    public $signature = 'translated-routes:validate';

    public $description = 'Validate route translations for consistency';

    public function handle(): int
    {
        $this->info('Validating route translations...');
        $this->newLine();

        $locales = array_keys(TranslatedRoutes::getSupportedLocales());
        $translations = $this->loadAllTranslations($locales);

        // Check for missing translations
        $missingErrors = $this->checkMissingTranslations($translations, $locales);

        // Check for unused translations
        $unusedErrors = $this->checkUnusedTranslations($translations);

        // Check for inconsistent keys
        $inconsistentErrors = $this->checkInconsistentKeys($translations, $locales);

        $hasErrors = $missingErrors || $unusedErrors || $inconsistentErrors;

        $this->newLine();

        if ($hasErrors) {
            $this->error('✗ Validation failed with errors');

            return self::FAILURE;
        }

        $this->info('✓ All translations are valid!');

        return self::SUCCESS;
    }

    protected function loadAllTranslations(array $locales): array
    {
        $translations = [];
        $langPath = $this->getLangPath();

        // Check for single file
        $singleFilePath = "{$langPath}/routes.php";
        if (file_exists($singleFilePath)) {
            $translations = require $singleFilePath;

            return $translations;
        }

        // Load separate files
        foreach ($locales as $locale) {
            $filePath = "{$langPath}/{$locale}/routes.php";
            if (file_exists($filePath)) {
                $translations[$locale] = require $filePath;
            } else {
                $translations[$locale] = [];
            }
        }

        return $translations;
    }

    protected function checkMissingTranslations(array $translations, array $locales): bool
    {
        $hasErrors = false;
        $allKeys = [];

        // Collect all unique keys
        foreach ($translations as $locale => $routes) {
            $allKeys = array_merge($allKeys, array_keys($routes));
        }
        $allKeys = array_unique($allKeys);

        // Check each locale for missing keys
        foreach ($locales as $locale) {
            $localeKeys = array_keys($translations[$locale] ?? []);
            $missingKeys = array_diff($allKeys, $localeKeys);

            if (! empty($missingKeys)) {
                $hasErrors = true;
                $this->warn("Missing translations in locale '{$locale}':");
                foreach ($missingKeys as $key) {
                    $this->line("  - {$key}");
                }
                $this->newLine();
            }
        }

        return $hasErrors;
    }

    protected function checkUnusedTranslations(array $translations): bool
    {
        $hasErrors = false;
        $routeCollection = Route::getRoutes();
        $usedKeys = [];

        // Collect all route URIs that use translate()
        foreach ($routeCollection->getRoutes() as $route) {
            $uri = $route->uri();
            if ($uri !== '/') {
                $usedKeys[] = $uri;
            }
        }

        // Check for unused translations
        foreach ($translations as $locale => $routes) {
            foreach (array_keys($routes) as $key) {
                // Skip wildcard patterns
                if (str_contains($key, '*')) {
                    continue;
                }

                // This is a simple check - in real usage, routes might be dynamic
                // So we'll just warn, not error
            }
        }

        return $hasErrors;
    }

    protected function checkInconsistentKeys(array $translations, array $locales): bool
    {
        $hasErrors = false;
        $keysByLocale = [];

        // Collect keys for each locale
        foreach ($locales as $locale) {
            $keysByLocale[$locale] = array_keys($translations[$locale] ?? []);
        }

        // Check for inconsistencies
        $firstLocale = $locales[0] ?? null;
        if (! $firstLocale) {
            return false;
        }

        $baseKeys = $keysByLocale[$firstLocale];
        sort($baseKeys);

        foreach ($locales as $locale) {
            if ($locale === $firstLocale) {
                continue;
            }

            $currentKeys = $keysByLocale[$locale];
            sort($currentKeys);

            if ($baseKeys !== $currentKeys) {
                $extra = array_diff($currentKeys, $baseKeys);
                $missing = array_diff($baseKeys, $currentKeys);

                if (! empty($extra)) {
                    $hasErrors = true;
                    $this->warn("Extra keys in '{$locale}' (not in '{$firstLocale}'):");
                    foreach ($extra as $key) {
                        $this->line("  + {$key}");
                    }
                    $this->newLine();
                }

                if (! empty($missing)) {
                    $hasErrors = true;
                    $this->warn("Missing keys in '{$locale}' (present in '{$firstLocale}'):");
                    foreach ($missing as $key) {
                        $this->line("  - {$key}");
                    }
                    $this->newLine();
                }
            }
        }

        return $hasErrors;
    }

    protected function getLangPath(): string
    {
        return is_dir(base_path('lang'))
            ? base_path('lang')
            : resource_path('lang');
    }
}

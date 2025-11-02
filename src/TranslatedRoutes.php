<?php

namespace Enes\TranslatedRoutes;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class TranslatedRoutes
{
    protected static array $cache = [];

    public function translate(string $key, ?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $routes = $this->loadRoutes($locale);

        // Exact match
        if (isset($routes[$key])) {
            return $routes[$key];
        }

        // Wildcard match
        foreach ($routes as $pattern => $translation) {
            if (str_contains($pattern, '*')) {
                $regex = '#^'.str_replace(['/', '*'], ['\/', '.*'], $pattern).'$#';
                if (preg_match($regex, $key)) {
                    // Replace wildcards in translation with matched segments
                    return $this->replaceWildcards($key, $pattern, $translation);
                }
            }
        }

        return $key;
    }

    protected function replaceWildcards(string $key, string $pattern, string $translation): string
    {
        // Extract segments from key that match wildcards in pattern
        $patternRegex = '#^'.str_replace(['/', '*'], ['\/', '(.+?)'], $pattern).'$#';
        $translationRegex = '#\*#';

        if (preg_match($patternRegex, $key, $keyMatches) && str_contains($translation, '*')) {
            array_shift($keyMatches); // Remove full match

            $result = $translation;
            foreach ($keyMatches as $match) {
                $result = preg_replace($translationRegex, $match, $result, 1);
            }

            return $result;
        }

        return $translation;
    }

    public function getSupportedLocales(): array
    {
        return Config::get('translated-routes.supported_locales', []);
    }

    public function getLocaleData(): array
    {
        $currentLocale = App::getLocale();
        $defaultLocale = Config::get('app.fallback_locale', 'en');
        $locales = [];

        foreach ($this->getSupportedLocales() as $code => $properties) {
            $locales[$code] = [
                'code' => $code,
                'name' => $properties['name'] ?? $code,
                'native' => $properties['native'] ?? $code,
                'active' => $code === $currentLocale,
            ];
        }

        return [
            'current' => $currentLocale,
            'default' => $defaultLocale,
            'supported' => $locales,
        ];
    }

    public function getNonLocalizedUrl(string $url): string
    {
        $supportedLocales = array_keys($this->getSupportedLocales());

        foreach ($supportedLocales as $locale) {
            $url = preg_replace('#^/'.$locale.'(/|$)#', '/', $url);
            $url = preg_replace('#/'.$locale.'(/|$)#', '/', $url);
        }

        return rtrim($url, '/') ?: '/';
    }

    public function clearCache(?string $locale = null): bool
    {
        if ($locale) {
            Cache::forget("translated_routes.{$locale}");
            unset(self::$cache[$locale]);

            return true;
        }

        foreach (array_keys($this->getSupportedLocales()) as $loc) {
            Cache::forget("translated_routes.{$loc}");
        }

        self::$cache = [];

        return true;
    }

    protected function loadRoutes(string $locale): array
    {
        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }

        if (Config::get('translated-routes.cache_enabled', true)) {
            self::$cache[$locale] = Cache::remember(
                "translated_routes.{$locale}",
                Config::get('translated-routes.cache_ttl', 86400),
                fn () => $this->loadRoutesFromFile($locale)
            );
        } else {
            self::$cache[$locale] = $this->loadRoutesFromFile($locale);
        }

        return self::$cache[$locale];
    }

    protected function loadRoutesFromFile(string $locale): array
    {
        $langPath = $this->getLangPath();

        // Option 1: Single file with all locales (lang/routes.php)
        $singleFilePath = "{$langPath}/routes.php";
        if (file_exists($singleFilePath)) {
            $allRoutes = require $singleFilePath;

            return $allRoutes[$locale] ?? [];
        }

        // Option 2: Separate file per locale (lang/{locale}/routes.php)
        $separateFilePath = "{$langPath}/{$locale}/routes.php";
        if (file_exists($separateFilePath)) {
            return require $separateFilePath;
        }

        return [];
    }

    protected function getLangPath(): string
    {
        return is_dir(base_path('lang'))
            ? base_path('lang')
            : resource_path('lang');
    }
}

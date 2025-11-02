<?php

namespace Enes\TranslatedRoutes\Commands;

use Enes\TranslatedRoutes\Facades\TranslatedRoutes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportTranslatedRoutes extends Command
{
    public $signature = 'translated-routes:export 
                        {--format=json : Export format (json, js, ts)}
                        {--output= : Output file path}';

    public $description = 'Export route translations for frontend frameworks';

    public function handle(): int
    {
        $format = $this->option('format');
        $output = $this->option('output') ?? $this->getDefaultOutput($format);

        $locales = array_keys(TranslatedRoutes::getSupportedLocales());
        $translations = $this->loadAllTranslations($locales);

        $content = match ($format) {
            'json' => $this->exportToJson($translations),
            'js' => $this->exportToJavaScript($translations),
            'ts' => $this->exportToTypeScript($translations),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };

        // Ensure directory exists
        $directory = dirname($output);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($output, $content);

        $this->info("✓ Translations exported to: {$output}");

        return self::SUCCESS;
    }

    protected function loadAllTranslations(array $locales): array
    {
        $translations = [];
        $langPath = $this->getLangPath();

        // Check for single file
        $singleFilePath = "{$langPath}/routes.php";
        if (file_exists($singleFilePath)) {
            return require $singleFilePath;
        }

        // Load separate files
        foreach ($locales as $locale) {
            $filePath = "{$langPath}/{$locale}/routes.php";
            if (file_exists($filePath)) {
                $translations[$locale] = require $filePath;
            }
        }

        return $translations;
    }

    protected function exportToJson(array $translations): string
    {
        return json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function exportToJavaScript(array $translations): string
    {
        $json = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<JS
// Auto-generated translated routes
// Generated at: {$this->getCurrentDateTime()}

export const translatedRoutes = {$json};

export function getRoute(key, locale = 'en') {
  return translatedRoutes[locale]?.[key] || key;
}

export default translatedRoutes;
JS;
    }

    protected function exportToTypeScript(array $translations): string
    {
        $json = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Get first locale to extract keys for type
        $firstLocale = array_key_first($translations);
        $keys = array_keys($translations[$firstLocale] ?? []);
        $keyType = empty($keys) ? 'string' : "'".implode("' | '", $keys)."'";
        $localeType = "'".implode("' | '", array_keys($translations))."'";
        $dateTime = $this->getCurrentDateTime();

        return <<<TS
// Auto-generated translated routes
// Generated at: {$dateTime}

export type RouteKey = {$keyType};

export type Locale = {$localeType};

export interface TranslatedRoutes {
  [locale: string]: {
    [key: string]: string;
  };
}

export const translatedRoutes: TranslatedRoutes = {$json};

export function getRoute(key: RouteKey, locale: Locale = 'en'): string {
  return translatedRoutes[locale]?.[key] || key;
}

export default translatedRoutes;
TS;
    }

    protected function getDefaultOutput(string $format): string
    {
        $extension = match ($format) {
            'json' => 'json',
            'js' => 'js',
            'ts' => 'ts',
            default => 'json',
        };

        return public_path("translations/routes.{$extension}");
    }

    protected function getLangPath(): string
    {
        return is_dir(base_path('lang'))
            ? base_path('lang')
            : resource_path('lang');
    }

    protected function getCurrentDateTime(): string
    {
        return now()->toDateTimeString();
    }
}

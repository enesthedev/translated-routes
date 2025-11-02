<?php

namespace Enes\TranslatedRoutes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Enes\TranslatedRoutes\Facades\TranslatedRoutes;

class TranslatedRoutesCommand extends Command
{
    public $signature = 'translated-routes:install';

    public $description = 'Create route translation files for all supported locales';

    public function handle(): int
    {
        $supportedLocales = config('translated-routes.supported_locales', []);

        if (empty($supportedLocales)) {
            $this->error('No supported locales found in config file.');

            return self::FAILURE;
        }

        $langPath = $this->getLangPath();

        foreach ($supportedLocales as $locale => $properties) {
            $localePath = "{$langPath}/{$locale}";

            if (! File::exists($localePath)) {
                File::makeDirectory($localePath, 0755, true);
            }

            $routesFilePath = "{$localePath}/routes.php";

            if (File::exists($routesFilePath)) {
                if (! $this->confirm("Route file already exists for locale '{$locale}'. Overwrite?", false)) {
                    continue;
                }
            }

            File::put($routesFilePath, $this->getRouteFileContent($locale));
            $this->info("✓ Created: {$routesFilePath}");
        }

        $this->newLine();
        $this->info('Route translation files created successfully!');

        return self::SUCCESS;
    }

    protected function getLangPath(): string
    {
        if (is_dir(base_path('lang'))) {
            return base_path('lang');
        }

        return resource_path('lang');
    }

    protected function getRouteFileContent(string $locale): string
    {
        $examples = [
            'en' => [
                'about' => 'about',
                'contact' => 'contact',
                'blog' => 'blog/{slug}',
            ],
            'tr' => [
                'about' => 'hakkimizda',
                'contact' => 'iletisim',
                'blog' => 'blog/{slug}',
            ],
        ];

        $routes = $examples[$locale] ?? $examples['en'];

        $content = "<?php\n\nreturn [\n";
        foreach ($routes as $key => $value) {
            $content .= "    '{$key}' => '{$value}',\n";
        }
        $content .= "];\n";

        return $content;
    }
}

<?php

namespace Enes\TranslatedRoutes\Commands;

use Enes\TranslatedRoutes\Facades\TranslatedRoutes;
use Illuminate\Console\Command;

class ClearTranslatedRoutesCache extends Command
{
    public $signature = 'translated-routes:clear {locale?}';

    public $description = 'Clear the translated routes cache';

    public function handle(): int
    {
        $locale = $this->argument('locale');

        if ($locale && ! array_key_exists($locale, TranslatedRoutes::getSupportedLocales())) {
            $this->error("Locale '{$locale}' is not supported.");

            return self::FAILURE;
        }

        TranslatedRoutes::clearCache($locale);

        if ($locale) {
            $this->info("✓ Cache cleared for locale: {$locale}");
        } else {
            $this->info('✓ Cache cleared for all locales');
        }

        return self::SUCCESS;
    }
}

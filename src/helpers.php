<?php

use Enes\TranslatedRoutes\Facades\TranslatedRoutes;

if (! function_exists('non_localized_url')) {
    function non_localized_url(string $url): string
    {
        return TranslatedRoutes::getNonLocalizedUrl($url);
    }
}

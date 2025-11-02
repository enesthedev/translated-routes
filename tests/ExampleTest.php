<?php

use Enes\TranslatedRoutes\Facades\TranslatedRoutes;
use Illuminate\Support\Facades\App;

beforeEach(function () {
    App::setLocale('en');
});

it('can translate a route key', function () {
    $translated = TranslatedRoutes::translate('about');

    expect($translated)->toBeString();
});

it('can get locale data for inertia', function () {
    $data = TranslatedRoutes::getLocaleData();

    expect($data)->toBeArray();
    expect($data)->toHaveKeys(['current', 'default', 'supported']);
    expect($data['current'])->toBe('en');
});

it('can get supported locales', function () {
    $locales = TranslatedRoutes::getSupportedLocales();

    expect($locales)->toBeArray();
    expect($locales)->toHaveKeys(['en', 'tr']);
});

it('can remove locale from url', function () {
    $url = '/tr/about-us';
    $cleaned = TranslatedRoutes::getNonLocalizedUrl($url);

    expect($cleaned)->toBe('/about-us');
});

it('can clear cache', function () {
    expect(TranslatedRoutes::clearCache())->toBeTrue();
    expect(TranslatedRoutes::clearCache('en'))->toBeTrue();
});

it('helper function works', function () {
    $url = non_localized_url('/tr/about');

    expect($url)->toBe('/about');
});

it('translates based on app locale', function () {
    App::setLocale('en');
    $translated = TranslatedRoutes::translate('about');
    expect($translated)->toBeString();

    App::setLocale('tr');
    $translated = TranslatedRoutes::translate('about');
    expect($translated)->toBeString();
});

it('supports wildcard translations', function () {
    App::setLocale('en');

    // Assuming wildcard pattern exists in lang files
    $translated = TranslatedRoutes::translate('blog/anything');
    expect($translated)->toBeString();
});

it('can translate with explicit locale parameter', function () {
    $translated = TranslatedRoutes::translate('about', 'en');
    expect($translated)->toBeString();

    $translated = TranslatedRoutes::translate('about', 'tr');
    expect($translated)->toBeString();
});

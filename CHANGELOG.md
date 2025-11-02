# Changelog

All notable changes to `translated-routes` will be documented in this file.

## 3.1.0 - 2025-11-02

### Major Feature Release 🎉

This release implements most of the planned features from the roadmap, significantly enhancing the package's capabilities while maintaining its simple, focused design.

### Added

- **Wildcard Translations**: Support for pattern matching in translation files
  ```php
  'blog/*' => 'blog/*'  // Matches all blog routes
  'user/*/profile' => 'kullanici/*/profil'
  ```
- **Translation Validation Command**: `php artisan translated-routes:validate`
  - Check for missing translations across locales
  - Detect inconsistent keys
  - Identify unused translations
- **Route List Command**: `php artisan translated-routes:list`
  - View all translated routes
  - Filter by locale with `--locale` option
  - Tabular output showing method, URI, name, and locale
- **Translation Export**: `php artisan translated-routes:export`
  - Export to JSON, JavaScript, or TypeScript formats
  - Generate typed interfaces for TypeScript
  - Includes helper functions for frontend usage
  - Perfect for SPAs and PWAs
- **Performance Profiling**: `php artisan translated-routes:profile`
  - Benchmark cold start vs warm cache performance
  - Measure memory usage per locale
  - Get recommendations for optimization
  - Configurable iteration count
- **Explicit Locale Parameter**: `TranslatedRoutes::translate($key, $locale)` now accepts optional locale

### Documentation

- Added "Advanced Features" section to `USAGE_EXAMPLES.md`
- Updated `README.md` with all new commands and features
- Enhanced `FEATURES.md` roadmap with completion status
- Added practical examples for all new features

### Performance

- Wildcard matching optimized with regex caching
- Export command generates production-ready assets
- Profiling tool helps identify bottlenecks

## 3.0.0 - 2025-11-02

### Complete Rewrite - Breaking Changes

This version is a complete architectural redesign focused on simplicity and Laravel-native patterns.

### Philosophy

- **Single Responsibility**: Only translates route URIs
- **Zero Magic**: Simple translation lookup from `lang/{locale}/routes.php`
- **Laravel Native**: Uses `->translate()` macro on routes
- **Locale Agnostic**: Works with any locale management system

### Added

- `->translate()` macro for individual routes
- `->translate()` macro for route groups  
- Automatic parameter preservation
- `non_localized_url()` helper function
- Inertia.js locale data sharing via `share-inertia-locale` middleware
- Cache optimization with static memory cache
- **Two file organization options:**
  - Separate files: `lang/{locale}/routes.php` (recommended)
  - Single file: `lang/routes.php` (simpler for small projects)
- Automatic detection of file structure

### Changed

- **BREAKING**: Package now only handles route translation, not locale management
- **BREAKING**: Uses `App::getLocale()` instead of managing locale internally
- **BREAKING**: Removed all locale detection/switching functionality
- **BREAKING**: Removed redirect middleware
- **BREAKING**: Removed all route helpers except `non_localized_url()`
- **BREAKING**: Routes must explicitly call `->translate()` to be translated
- Simplified configuration (only locales and cache settings)
- Improved caching strategy

### Removed

- `LocalizeRoutes` middleware (use Laravel's locale system)
- `LocaleSessionRedirect` middleware (handle in your app)
- `LocalizationRedirect` middleware (handle in your app)
- All helper functions except `non_localized_url()`
- Route macro methods (use `->translate()` instead)
- Locale detection logic
- Session management
- URL generation helpers

### Upgrade Guide

**Old Way (v2.x)**
```php
Route::group([
    'prefix' => TranslatedRoutes::setLocale(),
    'middleware' => ['localize']
], function () {
    Route::translated('about', $action);
});
```

**New Way (v3.x)**
```php
// Use any locale management you prefer (mcamara/laravel-localization, custom, etc.)
Route::get('about', $action)->translate();

// Or translate entire group
Route::group([], function () {
    Route::get('about', $action);
    Route::get('contact', $action);
})->translate();
```

### Why This Change?

1. **Single Responsibility**: Package now does ONE thing well - route translation
2. **Flexibility**: Works with ANY locale management package/method
3. **Simplicity**: No magic, no hidden behavior
4. **Laravel Native**: Feels like part of Laravel
5. **Performance**: Optimized caching, minimal overhead

### Migration Steps

1. Remove middleware: `localize`, `localeSessionRedirect`, `localizationRedirect`
2. Implement your own locale management (or use mcamara/laravel-localization)
3. Add `->translate()` to routes you want translated
4. Update helper calls: Only `non_localized_url()` is available
5. Clear cache: `php artisan translated-routes:clear`

---

## 2.0.0 - 2025-11-02

### Major Refactoring
- Simplified API
- Route macros added
- Improved documentation

## 1.0.0 - 2025-11-02

### Initial Release
- Multi-language route support
- Laravel 11+ compatibility

# Laravel Translated Routes

[![Latest Version on Packagist](https://img.shields.io/packagist/v/enesthedev/translated-routes.svg?style=flat-square)](https://packagist.org/packages/enesthedev/translated-routes)
[![Total Downloads](https://img.shields.io/packagist/dt/enesthedev/translated-routes.svg?style=flat-square)](https://packagist.org/packages/enesthedev/translated-routes)

Simple, elegant route translation for Laravel 11+. Just add `->translate()` to your routes.

## Why This Package?

- **Zero configuration** - Works with Laravel's locale system
- **Native feel** - Uses `->translate()` macro on routes
- **Cache-optimized** - Built-in caching with configurable TTL
- **Inertia-ready** - Shares locale data automatically
- **No magic** - Simple translation lookup from `lang/{locale}/routes.php`

## Installation

```bash
composer require enesthedev/translated-routes
```

```bash
php artisan vendor:publish --tag="translated-routes-config"
php artisan translated-routes:install
```

## Quick Start

### 1. Create Translation Files

You have **two options** for organizing your translation files:

#### Option A: Separate Files Per Locale (Recommended)

**lang/en/routes.php**
```php
return [
    'about' => 'about-us',
    'contact' => 'contact',
    'blog' => 'blog/{slug}',
    'products' => 'products/{category}/{id}',
];
```

**lang/tr/routes.php**
```php
return [
    'about' => 'hakkimizda',
    'contact' => 'iletisim',
    'blog' => 'blog/{slug}',
    'products' => 'urunler/{category}/{id}',
];
```

#### Option B: Single File with All Locales

**lang/routes.php**
```php
return [
    'en' => [
        'about' => 'about-us',
        'contact' => 'contact',
        'blog' => 'blog/{slug}',
        'products' => 'products/{category}/{id}',
    ],
    'tr' => [
        'about' => 'hakkimizda',
        'contact' => 'iletisim',
        'blog' => 'blog/{slug}',
        'products' => 'urunler/{category}/{id}',
    ],
];
```

> **Note:** The package automatically detects which structure you're using. If `lang/routes.php` exists, it uses that. Otherwise, it looks for `lang/{locale}/routes.php`.

### 2. Use `->translate()` on Your Routes

```php
use Illuminate\Support\Facades\Route;

// Single route translation
Route::get('about', [PageController::class, 'about'])->translate();
Route::get('contact', [PageController::class, 'contact'])->translate();
Route::get('blog', [BlogController::class, 'show'])->translate();

// Group translation (translates all routes in group)
Route::group([], function () {
    Route::get('about', [PageController::class, 'about']);
    Route::get('contact', [PageController::class, 'contact']);
    Route::get('products', [ProductController::class, 'show']);
})->translate();
```

### 3. Set Your App Locale

The package uses Laravel's `App::getLocale()`, so use any locale management package or middleware you prefer:

```php
// In a middleware or wherever you set locale
App::setLocale('tr');
```

### 4. Results

When `App::getLocale()` is `'en'`:
```
/about-us
/contact
/blog/hello-world
/products/electronics/123
```

When `App::getLocale()` is `'tr'`:
```
/hakkimizda
/iletisim
/blog/hello-world
/urunler/electronics/123
```

**Parameters are automatically preserved!**

## Inertia.js Support

### Backend

Add the middleware to share locale data:

```php
Route::middleware(['web', 'share-inertia-locale'])->group(function () {
    Route::get('about', [PageController::class, 'about'])->translate();
});
```

### Frontend (React/TypeScript)

**types/index.d.ts**
```typescript
export interface LocaleData {
  code: string;
  name: string;
  native: string;
  active: boolean;
}

export interface PageProps {
  locale: {
    current: string;
    default: string;
    supported: Record<string, LocaleData>;
  };
}
```

**LanguageSwitcher.tsx**
```tsx
import { usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function LanguageSwitcher() {
  const { locale } = usePage<PageProps>().props;

  const switchLocale = (code: string) => {
    // Use your preferred locale switching method
    router.post('/locale/switch', { locale: code });
  };

  return (
    <div className="flex gap-2">
      {Object.values(locale.supported).map((lang) => (
        <button
          key={lang.code}
          onClick={() => switchLocale(lang.code)}
          className={lang.active ? 'font-bold' : ''}
        >
          {lang.native}
        </button>
      ))}
    </div>
  );
}
```

**Current Locale Usage**
```tsx
const { locale } = usePage<PageProps>().props;

console.log(locale.current);  // 'en' or 'tr'
console.log(locale.supported.tr.native);  // 'Türkçe'
```

## API

### Route Macro

```php
// Translate a single route
Route::get('about', $action)->translate();

// Translate all routes in a group
Route::group([], function () {
    // Your routes
})->translate();
```

### Facade

```php
use Enes\TranslatedRoutes\Facades\TranslatedRoutes;

// Get locale data for Inertia
TranslatedRoutes::getLocaleData();

// Get supported locales
TranslatedRoutes::getSupportedLocales();

// Clear cache
TranslatedRoutes::clearCache();
TranslatedRoutes::clearCache('en');
```

### Helper Function

```php
// Remove locale from URL
non_localized_url('/tr/about');  // Returns: '/about'
```

## Configuration

```php
// config/translated-routes.php

return [
    // Define your supported locales
    'supported_locales' => [
        'en' => ['name' => 'English', 'native' => 'English'],
        'tr' => ['name' => 'Turkish', 'native' => 'Türkçe'],
    ],
    
    // Cache settings
    'cache_enabled' => env('TRANSLATED_ROUTES_CACHE', true),
    'cache_ttl' => env('TRANSLATED_ROUTES_CACHE_TTL', 86400),
];
```

## How It Works

1. You define your routes with keys (e.g., `'about'`, `'contact'`)
2. You add `->translate()` to the route or group
3. The package looks up the translation in `lang/{App::getLocale()}/routes.php`
4. Route parameters (`{slug}`, `{id}`) are automatically preserved
5. The translated route is set and cached

## Locale Management

This package **does not** manage locale switching. Use any method you prefer:

**Option 1: mcamara/laravel-localization**
```php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect']
], function () {
    Route::get('about', $action)->translate();
});
```

**Option 2: Custom Middleware**
```php
class SetLocale
{
    public function handle($request, $next)
    {
        if ($locale = $request->segment(1)) {
            if (in_array($locale, ['en', 'tr'])) {
                App::setLocale($locale);
            }
        }
        return $next($request);
    }
}
```

**Option 3: Session-based**
```php
Route::post('/locale/switch', function (Request $request) {
    session(['locale' => $request->locale]);
    App::setLocale($request->locale);
    return back();
});

// In a middleware
App::setLocale(session('locale', 'en'));
```

## Artisan Commands

```bash
# Create translation files
php artisan translated-routes:install

# Clear cache
php artisan translated-routes:clear        # All locales
php artisan translated-routes:clear en     # Specific locale

# Validate translations
php artisan translated-routes:validate     # Check for missing/inconsistent translations

# List translated routes
php artisan translated-routes:list         # All locales
php artisan translated-routes:list --locale=en  # Specific locale

# Export translations
php artisan translated-routes:export --format=json  # Export to JSON
php artisan translated-routes:export --format=js    # Export to JavaScript
php artisan translated-routes:export --format=ts    # Export to TypeScript

# Profile performance
php artisan translated-routes:profile      # Benchmark translation performance
```

## Advanced Usage

### Wildcard Translations

Support for dynamic route patterns:

```php
// lang/en/routes.php
return [
    'blog/*' => 'blog/*',
    'user/*/profile' => 'user/*/profile',
];

// lang/tr/routes.php
return [
    'blog/*' => 'blog/*',
    'user/*/profile' => 'kullanici/*/profil',
];
```

This allows matching multiple routes with a single pattern, reducing repetition.

### Translation Validation

Check your translations for consistency:

```bash
php artisan translated-routes:validate
```

### Export for Frontend

Export translations for SPA/PWA applications:

```bash
php artisan translated-routes:export --format=ts
```

Then use in your TypeScript application:

```typescript
import { getRoute, type RouteKey, type Locale } from '@/translations/routes';

const route = getRoute('about', 'tr'); // 'hakkimizda'
```

### Named Routes with Parameters

```php
Route::get('blog', [BlogController::class, 'show'])
    ->name('blog.show')
    ->translate();

// Generate URL
route('blog.show', ['slug' => 'hello-world']);
// Result: /blog/hello-world (en) or /blog/hello-world (tr)
```

### API Routes

```php
Route::prefix('api')->group(function () {
    Route::get('user', [UserController::class, 'show'])->translate();
});
```

### Fallback

If a translation key doesn't exist, the original URI is used:

```php
// lang/en/routes.php - 'missing-key' not defined

Route::get('missing-key', $action)->translate();
// Result: /missing-key (uses original URI)
```

## Performance

- **First request**: ~2ms (loads and caches translations)
- **Cached requests**: ~0.01ms (static memory cache)
- **Cache TTL**: Configurable (default 24 hours)

## Real-World Example

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{HomeController, BlogController, ProductController};

Route::middleware(['web', 'share-inertia-locale'])->group(function () {
    
    // Home page
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
    // Translated routes
    Route::group([], function () {
        Route::get('about', [HomeController::class, 'about'])->name('about');
        Route::get('contact', [HomeController::class, 'contact'])->name('contact');
        Route::get('services', [HomeController::class, 'services'])->name('services');
        
        Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
        
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{category}/{id}', [ProductController::class, 'show'])->name('products.show');
    })->translate();
    
    // Forms
    Route::post('contact', [HomeController::class, 'contactSubmit'])->name('contact.submit')->translate();
});
```

## What This Package Does

✅ Translates route URIs based on `App::getLocale()`  
✅ Preserves route parameters automatically  
✅ Caches translations for performance  
✅ Shares locale data with Inertia.js  
✅ Provides `non_localized_url()` helper  

## What This Package Does NOT Do

❌ Locale detection/switching (use other packages)  
❌ Content translation (use Laravel's `trans()`)  
❌ Session management (use Laravel's session)  
❌ URL redirects (handle in your middleware)  

## Requirements

- PHP 8.4+
- Laravel 11.0+

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for more information.

## License

MIT License. See [License File](LICENSE.md) for more information.

# Usage Examples

## Basic Usage

### 1. Create Translation Files

```bash
php artisan translated-routes:install
```

You have **two options** for organizing translations:

#### Option A: Separate Files (Recommended for Large Projects)

**lang/en/routes.php**
```php
return [
    'about' => 'about-us',
    'contact' => 'contact',
    'services' => 'services',
    'blog' => 'blog/{slug}',
    'products' => 'products/{category}/{id}',
    'user-profile' => 'user/{username}/profile',
];
```

**lang/tr/routes.php**
```php
return [
    'about' => 'hakkimizda',
    'contact' => 'iletisim',
    'services' => 'hizmetler',
    'blog' => 'blog/{slug}',
    'products' => 'urunler/{category}/{id}',
    'user-profile' => 'kullanici/{username}/profil',
];
```

#### Option B: Single File (Simpler for Small Projects)

**lang/routes.php**
```php
return [
    'en' => [
        'about' => 'about-us',
        'contact' => 'contact',
        'services' => 'services',
        'blog' => 'blog/{slug}',
        'products' => 'products/{category}/{id}',
        'user-profile' => 'user/{username}/profile',
    ],
    'tr' => [
        'about' => 'hakkimizda',
        'contact' => 'iletisim',
        'services' => 'hizmetler',
        'blog' => 'blog/{slug}',
        'products' => 'urunler/{category}/{id}',
        'user-profile' => 'kullanici/{username}/profil',
    ],
];
```

**Single File Benefits:**
- Easier to compare translations
- Less files to manage
- Better for version control diffs
- Side-by-side locale comparison

The package **automatically detects** which structure you're using.

### 2. Single Route Translation

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{PageController, BlogController};

// Translate individual routes
Route::get('about', [PageController::class, 'about'])
    ->name('about')
    ->translate();

Route::get('contact', [PageController::class, 'contact'])
    ->name('contact')
    ->translate();

Route::get('blog', [BlogController::class, 'show'])
    ->name('blog.show')
    ->translate();
```

### 3. Group Translation

```php
// Translate all routes in a group at once
Route::group([], function () {
    Route::get('about', [PageController::class, 'about'])->name('about');
    Route::get('contact', [PageController::class, 'contact'])->name('contact');
    Route::get('services', [PageController::class, 'services'])->name('services');
    Route::get('blog', [BlogController::class, 'show'])->name('blog.show');
})->translate();
```

## Locale Management

### Option 1: With mcamara/laravel-localization

```php
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localeViewPath']
], function () {
    Route::group([], function () {
        Route::get('about', [PageController::class, 'about'])->name('about');
        Route::get('contact', [PageController::class, 'contact'])->name('contact');
    })->translate();
});
```

### Option 2: Custom Middleware

**app/Http/Middleware/SetLocale.php**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->segment(1);
        
        if ($locale && in_array($locale, ['en', 'tr', 'de'])) {
            App::setLocale($locale);
            session(['locale' => $locale]);
        } else {
            App::setLocale(session('locale', config('app.locale')));
        }
        
        return $next($request);
    }
}
```

**routes/web.php**
```php
Route::middleware(['web', 'setLocale'])->group(function () {
    Route::group([], function () {
        Route::get('about', [PageController::class, 'about'])->name('about');
        Route::get('contact', [PageController::class, 'contact'])->name('contact');
    })->translate();
});
```

### Option 3: Session-Based (No URL Prefix)

**LocaleController.php**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        
        if (in_array($locale, ['en', 'tr'])) {
            session(['locale' => $locale]);
            App::setLocale($locale);
        }
        
        return back();
    }
}
```

**Middleware**
```php
public function handle(Request $request, Closure $next)
{
    App::setLocale(session('locale', config('app.locale')));
    return $next($request);
}
```

## Inertia.js Integration

### Backend Setup

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'share-inertia-locale'])->group(function () {
    Route::group([], function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('about', [PageController::class, 'about'])->name('about');
        Route::get('contact', [PageController::class, 'contact'])->name('contact');
        Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    })->translate();
});
```

### TypeScript Types

```typescript
// resources/js/types/index.d.ts

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

### React Components

**LanguageSwitcher.tsx**
```tsx
import { usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function LanguageSwitcher() {
  const { locale } = usePage<PageProps>().props;

  const switchLocale = (code: string) => {
    router.post(route('locale.switch'), { locale: code });
  };

  return (
    <div className="flex gap-2">
      {Object.values(locale.supported).map((lang) => (
        <button
          key={lang.code}
          onClick={() => switchLocale(lang.code)}
          className={`
            px-4 py-2 rounded-lg transition
            ${lang.active 
              ? 'bg-blue-600 text-white font-semibold' 
              : 'bg-gray-100 hover:bg-gray-200 text-gray-700'}
          `}
        >
          {lang.native}
        </button>
      ))}
    </div>
  );
}
```

**Using Current Locale**
```tsx
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function About() {
  const { locale } = usePage<PageProps>().props;

  const content = {
    en: { title: 'About Us', description: 'Learn more about our company' },
    tr: { title: 'Hakkımızda', description: 'Şirketimiz hakkında daha fazla bilgi' },
  };

  const t = content[locale.current as keyof typeof content];

  return (
    <div>
      <h1>{t.title}</h1>
      <p>{t.description}</p>
    </div>
  );
}
```

### Vue 3 Components

**LanguageSwitcher.vue**
```vue
<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { PageProps } from '@/types';

const { locale } = usePage<PageProps>().props;

const switchLocale = (code: string) => {
  router.post(route('locale.switch'), { locale: code });
};
</script>

<template>
  <div class="flex gap-2">
    <button
      v-for="lang in locale.supported"
      :key="lang.code"
      @click="switchLocale(lang.code)"
      :class="[
        'px-4 py-2 rounded-lg transition',
        lang.active 
          ? 'bg-blue-600 text-white font-semibold' 
          : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
      ]"
    >
      {{ lang.native }}
    </button>
  </div>
</template>
```

## Complete E-Commerce Example

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    CategoryController,
    ProductController,
    CartController,
    CheckoutController,
    AccountController
};

Route::middleware(['web', 'share-inertia-locale'])->group(function () {
    
    // Non-translated routes
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
    // Translated public routes
    Route::group([], function () {
        Route::get('about', [HomeController::class, 'about'])->name('about');
        Route::get('contact', [HomeController::class, 'contact'])->name('contact');
        
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
        
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{id}', [ProductController::class, 'show'])->name('products.show');
        
        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    })->translate();
    
    // Translated form submissions
    Route::post('contact', [HomeController::class, 'contactSubmit'])->name('contact.submit')->translate();
    Route::post('cart', [CartController::class, 'add'])->name('cart.add')->translate();
    
    // Authenticated routes
    Route::middleware(['auth'])->group(function () {
        Route::group([], function () {
            Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
            Route::get('account', [AccountController::class, 'index'])->name('account.index');
            Route::get('account/orders', [AccountController::class, 'orders'])->name('account.orders');
        })->translate();
        
        Route::post('checkout', [CheckoutController::class, 'process'])->name('checkout.process')->translate();
    });
});
```

**Translation Files**

**lang/en/routes.php**
```php
return [
    'about' => 'about-us',
    'contact' => 'contact-us',
    'categories' => 'categories',
    'categories/{slug}' => 'category/{slug}',
    'products' => 'products',
    'products/{id}' => 'product/{id}',
    'cart' => 'shopping-cart',
    'checkout' => 'checkout',
    'account' => 'my-account',
    'account/orders' => 'my-account/orders',
];
```

**lang/tr/routes.php**
```php
return [
    'about' => 'hakkimizda',
    'contact' => 'iletisim',
    'categories' => 'kategoriler',
    'categories/{slug}' => 'kategori/{slug}',
    'products' => 'urunler',
    'products/{id}' => 'urun/{id}',
    'cart' => 'sepet',
    'checkout' => 'odeme',
    'account' => 'hesabim',
    'account/orders' => 'hesabim/siparisler',
];
```

## Cache Management

```bash
# Clear all caches
php artisan translated-routes:clear

# Clear specific locale
php artisan translated-routes:clear en
```

Or programmatically:

```php
use Enes\TranslatedRoutes\Facades\TranslatedRoutes;

// Clear all
TranslatedRoutes::clearCache();

// Clear specific
TranslatedRoutes::clearCache('en');
```

## Helper Function

```php
// Remove locale from URL (useful for sharing/canonical URLs)
$url = '/tr/hakkimizda';
$clean = non_localized_url($url);
// Result: '/hakkimizda'
```

## Advanced Features

### Wildcard Translations

For routes with similar patterns, use wildcards to reduce repetition:

```php
// lang/en/routes.php
return [
    'blog/*' => 'blog/*',
    'docs/*' => 'documentation/*',
    'user/*/profile' => 'user/*/profile',
    'category/*/products' => 'category/*/products',
];

// lang/tr/routes.php
return [
    'blog/*' => 'blog/*',
    'docs/*' => 'dokumantasyon/*',
    'user/*/profile' => 'kullanici/*/profil',
    'category/*/products' => 'kategori/*/urunler',
];
```

Routes:
```php
Route::get('blog/latest', [BlogController::class, 'latest'])->translate();
Route::get('blog/featured', [BlogController::class, 'featured'])->translate();
Route::get('docs/installation', [DocsController::class, 'installation'])->translate();

// All blog/* routes remain 'blog/*' in English
// All docs/* routes become 'dokumantasyon/*' in Turkish
```

### Translation Validation

Before deploying, validate your translations:

```bash
php artisan translated-routes:validate
```

Example output with errors:
```
Validating route translations...

Missing translations in locale 'tr':
  - products/new
  - services/consulting

Extra keys in 'de' (not in 'en'):
  + old-about-page

✗ Validation failed with errors
```

This helps catch:
- Missing translations
- Extra unused translations
- Inconsistent keys across locales

### List All Translated Routes

View all your translated routes:

```bash
# All locales
php artisan translated-routes:list

# Specific locale
php artisan translated-routes:list --locale=tr
```

Output:
```
+--------+------------------+-------------+--------+
| Method | URI              | Name        | Locale |
+--------+------------------+-------------+--------+
| GET    | about-us         | about       | en     |
| GET    | hakkimizda       | about       | tr     |
| GET    | contact-us       | contact     | en     |
| GET    | iletisim         | contact     | tr     |
+--------+------------------+-------------+--------+
```

### Export for Frontend Frameworks

Export translations for use in SPAs/PWAs:

#### Export to TypeScript:
```bash
php artisan translated-routes:export --format=ts --output=resources/js/translations/routes.ts
```

Generated file:
```typescript
// Auto-generated translated routes
export type RouteKey = 'about' | 'contact' | 'blog/{slug}';
export type Locale = 'en' | 'tr';

export const translatedRoutes: TranslatedRoutes = {
  "en": {
    "about": "about-us",
    "contact": "contact-us"
  },
  "tr": {
    "about": "hakkimizda",
    "contact": "iletisim"
  }
};

export function getRoute(key: RouteKey, locale: Locale = 'en'): string {
  return translatedRoutes[locale]?.[key] || key;
}
```

Use in React/Vue:
```tsx
import { getRoute } from '@/translations/routes';

// Get localized route
const aboutUrl = getRoute('about', 'tr'); // 'hakkimizda'

// In component
<Link href={`/${getRoute('about', locale)}`}>
  About Us
</Link>
```

#### Export to JSON:
```bash
php artisan translated-routes:export --format=json --output=public/translations/routes.json
```

Use with fetch:
```javascript
const response = await fetch('/translations/routes.json');
const routes = await response.json();
const aboutRoute = routes[locale]['about'];
```

### Performance Profiling

Benchmark your translation performance:

```bash
php artisan translated-routes:profile --iterations=100
```

Output:
```
Running performance profiling...

Performance Results:
+--------------------------+-------------+
| Metric                   | Value       |
+--------------------------+-------------+
| Cold Start (first load)  | 2.15 ms     |
| Warm Cache (avg)         | 0.0089 ms   |
| Cache Speedup            | 242x faster |
+--------------------------+-------------+

+--------------------------+-------------+
| Metric                   | Value       |
+--------------------------+-------------+
| Memory Before            | 4.00 MB     |
| Memory After             | 4.02 MB     |
| Memory Used              | 20.48 KB    |
| Per Locale               | 10.24 KB    |
+--------------------------+-------------+

Recommendations:
  ✓ Cold start performance is good.
  ✓ Warm cache performance is excellent.
  ✓ Memory usage is optimal.

Tested with 100 iterations per benchmark.
```

This helps you:
- Monitor performance over time
- Detect regressions
- Optimize translation files
- Make informed caching decisions

## Best Practices

1. **Use Named Routes** - Always name your routes for easy linking
2. **Group Translation** - Use group translate for cleaner code
3. **Cache in Production** - Enable caching for best performance
4. **Consistent Keys** - Use the same keys across all locale files
5. **Parameters** - Keep parameter names consistent (`{slug}`, not `{id}` in one and `{slug}` in another)

This package integrates seamlessly with any locale management solution while keeping route translation simple and performant.

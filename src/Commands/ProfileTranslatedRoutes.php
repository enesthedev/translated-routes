<?php

namespace Enes\TranslatedRoutes\Commands;

use Enes\TranslatedRoutes\Facades\TranslatedRoutes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class ProfileTranslatedRoutes extends Command
{
    public $signature = 'translated-routes:profile {--iterations=100 : Number of iterations for benchmarking}';

    public $description = 'Profile and benchmark route translation performance';

    public function handle(): int
    {
        $iterations = (int) $this->option('iterations');

        $this->info('Running performance profiling...');
        $this->newLine();

        // Clear cache for cold start test
        TranslatedRoutes::clearCache();

        // Test cold start
        $coldStartTime = $this->benchmarkColdStart();

        // Test warm cache
        $warmCacheTime = $this->benchmarkWarmCache($iterations);

        // Test memory usage
        $memoryUsage = $this->measureMemoryUsage();

        // Display results
        $this->displayResults($coldStartTime, $warmCacheTime, $memoryUsage, $iterations);

        return self::SUCCESS;
    }

    protected function benchmarkColdStart(): float
    {
        // Clear all caches
        TranslatedRoutes::clearCache();

        $start = microtime(true);

        // Load first translation
        $locale = array_key_first(TranslatedRoutes::getSupportedLocales());
        App::setLocale($locale);
        TranslatedRoutes::translate('about');

        $end = microtime(true);

        return ($end - $start) * 1000; // Convert to milliseconds
    }

    protected function benchmarkWarmCache(int $iterations): float
    {
        $locale = array_key_first(TranslatedRoutes::getSupportedLocales());
        App::setLocale($locale);

        // Warm up
        TranslatedRoutes::translate('about');

        // Benchmark
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            TranslatedRoutes::translate('about');
            TranslatedRoutes::translate('contact');
            TranslatedRoutes::translate('blog/{slug}');
        }

        $end = microtime(true);

        $totalTime = ($end - $start) * 1000; // Convert to milliseconds

        return $totalTime / ($iterations * 3); // Average per translation
    }

    protected function measureMemoryUsage(): array
    {
        $before = memory_get_usage(true);

        // Load all locales
        foreach (array_keys(TranslatedRoutes::getSupportedLocales()) as $locale) {
            App::setLocale($locale);
            TranslatedRoutes::translate('about');
        }

        $after = memory_get_usage(true);

        return [
            'before' => $before,
            'after' => $after,
            'diff' => $after - $before,
        ];
    }

    protected function displayResults(float $coldStart, float $warmCache, array $memory, int $iterations): void
    {
        $this->info('Performance Results:');
        $this->newLine();

        // Translation Performance
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cold Start (first load)', number_format($coldStart, 2).' ms'],
                ['Warm Cache (avg)', number_format($warmCache, 4).' ms'],
                ['Cache Speedup', number_format($coldStart / $warmCache, 0).'x faster'],
            ]
        );

        $this->newLine();

        // Memory Usage
        $this->table(
            ['Metric', 'Value'],
            [
                ['Memory Before', $this->formatBytes($memory['before'])],
                ['Memory After', $this->formatBytes($memory['after'])],
                ['Memory Used', $this->formatBytes($memory['diff'])],
                ['Per Locale', $this->formatBytes($memory['diff'] / count(TranslatedRoutes::getSupportedLocales()))],
            ]
        );

        $this->newLine();

        // Recommendations
        $this->info('Recommendations:');
        if ($coldStart > 5) {
            $this->warn('  ⚠ Cold start time is high. Consider using route caching in production.');
        } else {
            $this->line('  ✓ Cold start performance is good.');
        }

        if ($warmCache > 0.1) {
            $this->warn('  ⚠ Warm cache time could be better. Check cache configuration.');
        } else {
            $this->line('  ✓ Warm cache performance is excellent.');
        }

        if ($memory['diff'] > 1024 * 1024) { // > 1MB
            $this->warn('  ⚠ Memory usage is high. Consider optimizing translation files.');
        } else {
            $this->line('  ✓ Memory usage is optimal.');
        }

        $this->newLine();
        $this->comment("Tested with {$iterations} iterations per benchmark.");
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, 2).' '.$units[$i];
    }
}

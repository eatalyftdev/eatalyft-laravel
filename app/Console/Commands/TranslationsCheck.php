<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Translation\TranslationServiceProvider;
use Symfony\Component\Finder\Finder;

class TranslationsCheck extends Command
{
    protected $signature = 'translations:check 
                            {--locale=en : Base locale to check against}
                            {--fail-on-missing : Exit with error code if missing translations found}
                            {--show-unused : Show unused translation keys}';

    protected $description = 'Check for missing or unused translation keys';

    public function handle(): int
    {
        $baseLocale = $this->option('locale');
        $failOnMissing = $this->option('fail-on-missing');
        $showUnused = $this->option('show-unused');

        $this->info("Checking translations against locale: {$baseLocale}");

        $langPath = resource_path('lang');
        $baseLocaleFile = $langPath . '/' . $baseLocale . '.json';

        if (!File::exists($baseLocaleFile)) {
            $this->error("Base locale file not found: {$baseLocaleFile}");
            return Command::FAILURE;
        }

        // Get all translation keys from base locale
        $baseKeys = $this->getTranslationKeysFromFile($baseLocaleFile);
        $this->info("Found " . count($baseKeys) . " translation keys in {$baseLocale}");

        // Get all locales
        $localeFiles = File::glob($langPath . '/*.json');
        $locales = [];
        foreach ($localeFiles as $file) {
            $locale = basename($file, '.json');
            if ($locale !== $baseLocale) {
                $locales[$locale] = $file;
            }
        }

        $allMissing = [];
        $allUnused = [];

        foreach ($locales as $locale => $localeFile) {
            $localeKeys = $this->getTranslationKeysFromFile($localeFile);

            $missing = array_diff($baseKeys, $localeKeys);
            $unused = array_diff($localeKeys, $baseKeys);

            if (!empty($missing)) {
                $this->warn("Locale {$locale}: " . count($missing) . " missing keys");
                $allMissing[$locale] = $missing;
            } else {
                $this->info("Locale {$locale}: No missing keys ✓");
            }

            if ($showUnused && !empty($unused)) {
                $this->warn("Locale {$locale}: " . count($unused) . " unused keys");
                $allUnused[$locale] = $unused;
            }
        }

        // Check for keys used in code but not in translations
        $usedInCode = $this->getUsedKeysInCode();
        $missingInCode = array_diff($usedInCode, $baseKeys);
        
        if (!empty($missingInCode)) {
            $this->warn("Found " . count($missingInCode) . " keys used in code but not in {$baseLocale} translations");
            foreach (array_slice($missingInCode, 0, 20) as $key) {
                $this->line("  - {$key}");
            }
            if (count($missingInCode) > 20) {
                $this->line("  ... and " . (count($missingInCode) - 20) . " more");
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Base locale ({$baseLocale}): " . count($baseKeys) . " keys");
        
        foreach ($locales as $localePath) {
            $locale = basename($localePath);
            $missingCount = count($allMissing[$locale] ?? []);
            $unusedCount = count($allUnused[$locale] ?? []);
            $status = $missingCount === 0 ? '✓' : '✗';
            $this->info("{$locale}: {$status} Missing: {$missingCount}" . ($showUnused ? ", Unused: {$unusedCount}" : ""));
        }

        $totalMissing = array_sum(array_map('count', $allMissing));
        
        if ($totalMissing > 0) {
            $this->error("Total missing translations: {$totalMissing}");
            
            if ($failOnMissing) {
                return Command::FAILURE;
            }
        } else {
            $this->info('All locales have complete translations! ✓');
        }

        return Command::SUCCESS;
    }

    protected function getTranslationKeysFromFile(string $file): array
    {
        $keys = [];
        $translations = json_decode(File::get($file), true);
        if (is_array($translations)) {
            $this->flattenKeys($translations, '', $keys);
        }
        return $keys;
    }

    protected function flattenKeys(array $array, string $prefix, array &$keys): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $this->flattenKeys($value, $fullKey, $keys);
            } else {
                $keys[] = $fullKey;
            }
        }
    }

    protected function getUsedKeysInCode(): array
    {
        $keys = [];
        $patterns = [
            '/__\([\'"]([^\'"]+)[\'"]\)/',
            '/@lang\([\'"]([^\'"]+)[\'"]\)/',
            '/trans\([\'"]([^\'"]+)[\'"]\)/',
            '/Lang::get\([\'"]([^\'"]+)[\'"]\)/',
        ];

        $directories = [
            app_path(),
            resource_path('views'),
            resource_path('js'),
        ];

        foreach ($directories as $dir) {
            if (!File::isDirectory($dir)) {
                continue;
            }

            $files = (new Finder())
                ->in($dir)
                ->name('*.php')
                ->name('*.blade.php')
                ->name('*.vue')
                ->name('*.js')
                ->name('*.ts')
                ->name('*.tsx');

            foreach ($files as $file) {
                $content = $file->getContents();
                
                foreach ($patterns as $pattern) {
                    preg_match_all($pattern, $content, $matches);
                    if (!empty($matches[1])) {
                        $keys = array_merge($keys, $matches[1]);
                    }
                }
            }
        }

        return array_unique($keys);
    }
}
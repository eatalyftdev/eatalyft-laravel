<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return AppSetting::getValue($key, $default);
    }

    public function getTyped(string $key, mixed $default = null): mixed
    {
        return AppSetting::getTypedValue($key, $default);
    }

    public function set(string $key, mixed $value, array $options = []): AppSetting
    {
        return AppSetting::setValue(
            $key,
            $value,
            $options['type'] ?? null,
            $options['group'] ?? null,
            $options['label'] ?? null,
            $options['description'] ?? null,
            $options['validation_rules'] ?? null,
            $options['is_public'] ?? false,
            $options['is_editable'] ?? true,
            $options['sort_order'] ?? 0,
            $options['updated_by'] ?? null
        );
    }

    public function getGroup(string $group): array
    {
        return AppSetting::getGroup($group);
    }

    public function getAllGroups(): array
    {
        return AppSetting::getAllGroups();
    }

    public function getEditable(): array
    {
        return AppSetting::getEditableSettings();
    }

    public function clearCache(): void
    {
        AppSetting::clearCache();
    }

    // Convenience methods for common settings
    public function getSiteName(): string
    {
        return $this->get('site.name', config('app.name', 'Eatalyft'));
    }

    public function getSiteLogo(): ?string
    {
        return $this->get('site.logo');
    }

    public function getSiteContacts(): array
    {
        return $this->get('site.contacts', []);
    }

    public function getSeoDefaults(): array
    {
        return $this->get('site.seo', []);
    }

    public function getLegalPages(): array
    {
        return $this->get('site.legal', []);
    }

    public function getDefaultLanguage(): string
    {
        return $this->get('app.default_language', 'en');
    }

    public function getDefaultCurrency(): string
    {
        return $this->get('app.default_currency', 'XAF');
    }

    public function isMaintenanceMode(): bool
    {
        return $this->get('app.maintenance_mode', false);
    }

    public function getFeatureToggles(): array
    {
        return $this->get('features', [
            'bus' => true,
            'hotels' => true,
            'listings' => true,
            'esim' => true,
            'boxo' => true,
            'handyman' => false,
        ]);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        $features = $this->getFeatureToggles();
        return $features[$feature] ?? false;
    }
}
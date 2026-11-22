<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory, Auditable;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'validation_rules',
        'is_public',
        'is_editable',
        'sort_order',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'validation_rules' => 'array',
            'is_public' => 'boolean',
            'is_editable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::getCachedSettings()->get($key);
        if ($setting !== null) {
            return $setting;
        }
        $model = self::find($key);
        return $model?->value ?? $default;
    }

    public static function getTypedValue(string $key, mixed $default = null): mixed
    {
        $setting = self::find($key);
        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    public static function setValue(string $key, mixed $value, ?string $type = null, ?string $group = null, ?string $label = null, ?string $description = null, ?array $validationRules = null, ?bool $isPublic = false, ?bool $isEditable = true, ?int $sortOrder = 0, ?int $updatedBy = null): self
    {
        $type ??= self::inferType($value);

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'label' => $label,
                'description' => $description,
                'validation_rules' => $validationRules,
                'is_public' => $isPublic,
                'is_editable' => $isEditable,
                'sort_order' => $sortOrder,
                'updated_by' => $updatedBy ?? auth()->id(),
            ]
        );

        self::clearCache();

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return self::getCachedSettings()
            ->filter(fn ($_, $key) => str_starts_with($key, $group . '.'))
            ->mapWithKeys(fn ($value, $key) => [str_replace($group . '.', '', $key) => $value])
            ->toArray();
    }

    public static function getAllGroups(): array
    {
        return self::getCachedSettings()
            ->groupBy(fn ($_, $key) => explode('.', $key)[0])
            ->map(fn ($group) => $group->mapWithKeys(fn ($value, $key) => [str_replace(explode('.', $key)[0] . '.', '', $key) => $value])->toArray())
            ->toArray();
    }

    public static function getEditableSettings(): array
    {
        return self::where('is_editable', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(fn ($group) => $group->mapWithKeys(fn ($setting) => [$setting->key => [
                'value' => self::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'label' => $setting->label,
                'description' => $setting->description,
                'validation_rules' => $setting->validation_rules,
                'group' => $setting->group,
            ]])->toArray())
            ->toArray();
    }

    protected static function getCachedSettings(): \Illuminate\Support\Collection
    {
        return cache()->remember('app_settings', 3600, function () {
            return self::all()->mapWithKeys(fn ($setting) => [
                $setting->key => self::castValue($setting->value, $setting->type)
            ]);
        });
    }

    public static function clearCache(): void
    {
        cache()->forget('app_settings');
    }

    protected static function castValue(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_array($value) ? $value : json_decode($value, true),
            default => (string) $value,
        };
    }

    protected static function inferType(mixed $value): string
    {
        if (is_bool($value)) return 'bool';
        if (is_int($value)) return 'int';
        if (is_float($value)) return 'float';
        if (is_array($value)) return 'array';
        return 'string';
    }

    protected function getAdditionalAuditExcludeAttributes(): array
    {
        return ['value'];
    }

    protected function shouldAudit(string $action): bool
    {
        return $action !== 'created' || $this->is_editable;
    }
}
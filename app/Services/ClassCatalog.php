<?php

namespace App\Services;

use Illuminate\Validation\Rule;

class ClassCatalog {
    public static function groups(): array {
        $groups = [];
        foreach (config('classes') as $name => $count) {
            $groups[$name] = array_map(fn ($number) => $name.' '.$number, range(1, $count));
        }
        return $groups;
    }

    public static function all(): array {
        return array_merge(...array_values(self::groups()));
    }

    public static function rules(?string $currentClass = null): array {
        $allowed = self::all();
        // Existing class names can be kept while the member's other fields are edited.
        if ($currentClass !== null && $currentClass !== '') { $allowed[] = $currentClass; }
        return ['nullable', 'string', 'max:100', Rule::in($allowed)];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    public const FIELD_TYPES = [
        'text', 'textarea', 'number', 'email', 'phone', 'date', 'dropdown', 'radio',
        'checkbox', 'file', 'section', 'rating', 'url',
    ];

    public function defaultSchema(string $title = 'Untitled form'): array
    {
        return [
            'title' => $title,
            'description' => '',
            'steps' => [[
                'id' => (string) Str::uuid(),
                'title' => 'Step 1',
                'fields' => [],
            ]],
            'logic' => [],
        ];
    }

    public function normalize(array $schema): array
    {
        $schema['title'] = trim((string) ($schema['title'] ?? 'Untitled form')) ?: 'Untitled form';
        $schema['description'] = (string) ($schema['description'] ?? '');
        $schema['steps'] = array_values($schema['steps'] ?? []);
        $seenKeys = [];

        if ($schema['steps'] === []) {
            $schema['steps'][] = ['id' => (string) Str::uuid(), 'title' => 'Step 1', 'fields' => []];
        }

        foreach ($schema['steps'] as $stepIndex => $step) {
            $step['id'] = (string) ($step['id'] ?? Str::uuid());
            $step['title'] = trim((string) ($step['title'] ?? 'Step '.($stepIndex + 1))) ?: 'Step '.($stepIndex + 1);
            $step['fields'] = array_values($step['fields'] ?? []);

            foreach ($step['fields'] as $fieldIndex => $field) {
                $type = in_array($field['type'] ?? '', self::FIELD_TYPES, true) ? $field['type'] : 'text';
                $label = trim((string) ($field['label'] ?? Str::headline($type)));
                $key = Str::snake((string) ($field['key'] ?? $label));
                $baseKey = $key ?: 'field';
                $suffix = 2;
                while (isset($seenKeys[$key])) {
                    $key = $baseKey.'_'.$suffix++;
                }
                $seenKeys[$key] = true;

                $step['fields'][$fieldIndex] = [
                    'id' => (string) ($field['id'] ?? Str::uuid()),
                    'type' => $type,
                    'label' => $label ?: Str::headline($key),
                    'key' => $key,
                    'placeholder' => (string) ($field['placeholder'] ?? ''),
                    'helpText' => (string) ($field['helpText'] ?? ''),
                    'default' => $field['default'] ?? null,
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => $this->normalizeOptions($field['options'] ?? []),
                    'validation' => $this->normalizeValidation($field['validation'] ?? [], $type),
                    'visibility' => $field['visibility'] ?? null,
                ];
            }

            $schema['steps'][$stepIndex] = $step;
        }

        $schema['logic'] = array_values($schema['logic'] ?? []);

        return $schema;
    }

    public function validate(array $schema): array
    {
        $schema = $this->normalize($schema);
        $errors = [];
        $keys = [];

        foreach ($schema['steps'] as $stepIndex => $step) {
            if (($step['fields'] ?? []) === []) {
                continue;
            }

            foreach ($step['fields'] as $fieldIndex => $field) {
                $path = 'steps.'.($stepIndex + 1).'.fields.'.($fieldIndex + 1);
                if (! in_array($field['type'], self::FIELD_TYPES, true)) {
                    $errors[$path.'.type'][] = 'Unsupported field type.';
                }
                if ($field['type'] !== 'section' && blank($field['key'])) {
                    $errors[$path.'.key'][] = 'Field key is required.';
                }
                if (isset($keys[$field['key']])) {
                    $errors[$path.'.key'][] = 'Field keys must be unique.';
                }
                $keys[$field['key']] = true;
                if (in_array($field['type'], ['dropdown', 'radio', 'checkbox'], true) && count($field['options']) < 1) {
                    $errors[$path.'.options'][] = 'Choice fields require at least one option.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $schema;
    }

    public function rulesFor(array $schema): array
    {
        $rules = [];

        foreach ($this->fields($schema) as $field) {
            if ($field['type'] === 'section') {
                continue;
            }

            $fieldRules = [$field['required'] ? 'required' : 'nullable'];
            $validation = $field['validation'] ?? [];

            $fieldRules[] = match ($field['type']) {
                'email' => 'email',
                'number', 'rating' => 'numeric',
                'date' => 'date',
                'url' => 'url',
                'file' => 'file',
                'checkbox' => 'array',
                default => 'string',
            };

            foreach (['min', 'max', 'minLength', 'maxLength'] as $rule) {
                if (isset($validation[$rule]) && $validation[$rule] !== '') {
                    $laravelRule = str_contains($rule, 'Length') ? str_replace('Length', '', $rule) : $rule;
                    $fieldRules[] = strtolower($laravelRule).':'.$validation[$rule];
                }
            }
            if (($validation['email'] ?? false) && ! in_array('email', $fieldRules, true)) {
                $fieldRules[] = 'email';
            }
            if (($validation['url'] ?? false) && ! in_array('url', $fieldRules, true)) {
                $fieldRules[] = 'url';
            }
            if (! blank($validation['regex'] ?? null)) {
                $fieldRules[] = 'regex:'.$validation['regex'];
            }
            if ($field['type'] === 'file') {
                if (! blank($validation['fileTypes'] ?? null)) {
                    $fieldRules[] = 'mimes:'.implode(',', Arr::wrap($validation['fileTypes']));
                }
                if (! blank($validation['maxFileSizeKb'] ?? null)) {
                    $fieldRules[] = 'max:'.$validation['maxFileSizeKb'];
                }
            }

            $rules[$field['key']] = $fieldRules;
        }

        return $rules;
    }

    public function fields(array $schema): array
    {
        return collect($schema['steps'] ?? [])->flatMap(fn ($step) => $step['fields'] ?? [])->values()->all();
    }

    private function normalizeOptions(array $options): array
    {
        return collect($options)->map(function ($option) {
            if (is_array($option)) {
                $label = (string) ($option['label'] ?? $option['value'] ?? 'Option');
                $value = (string) ($option['value'] ?? Str::snake($label));
                return ['label' => $label, 'value' => $value];
            }

            return ['label' => (string) $option, 'value' => Str::snake((string) $option)];
        })->filter(fn ($option) => $option['label'] !== '')->values()->all();
    }

    private function normalizeValidation(array $validation, string $type): array
    {
        return array_filter([
            'min' => $validation['min'] ?? null,
            'max' => $validation['max'] ?? null,
            'minLength' => $validation['minLength'] ?? null,
            'maxLength' => $validation['maxLength'] ?? null,
            'email' => (bool) ($validation['email'] ?? $type === 'email'),
            'url' => (bool) ($validation['url'] ?? $type === 'url'),
            'numeric' => (bool) ($validation['numeric'] ?? in_array($type, ['number', 'rating'], true)),
            'regex' => $validation['regex'] ?? null,
            'fileTypes' => $validation['fileTypes'] ?? null,
            'maxFileSizeKb' => $validation['maxFileSizeKb'] ?? null,
        ], fn ($value) => $value !== null && $value !== false && $value !== '');
    }
}

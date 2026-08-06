<?php

namespace App\Services;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory as WordFactory;

class ImportParserService
{
    public function __construct(private readonly FormSchemaService $schemas)
    {
    }

    public function parse(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'docx' => $this->parseDocx($path),
            'xlsx' => $this->parseXlsx($path),
            default => ['schema' => $this->schemas->defaultSchema('Imported form'), 'warnings' => ['Unsupported extension.']],
        };
    }

    private function parseDocx(string $path): array
    {
        $document = WordFactory::load($path);
        $steps = [];
        $current = ['id' => (string) Str::uuid(), 'title' => 'Imported questions', 'fields' => []];
        $warnings = [];

        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text = trim($this->textFromElement($element));
                if ($text === '') {
                    continue;
                }
                if (str_ends_with($text, ':') || preg_match('/^(section|step)\b/i', $text)) {
                    if ($current['fields'] !== []) {
                        $steps[] = $current;
                    }
                    $current = ['id' => (string) Str::uuid(), 'title' => trim($text, ':'), 'fields' => []];
                    continue;
                }
                $current['fields'][] = $this->fieldFromText($text);
            }
        }

        if ($current['fields'] !== []) {
            $steps[] = $current;
        }
        if ($steps === []) {
            $warnings[] = 'No questions detected; created an empty mapping for manual correction.';
            $steps[] = $current;
        }

        return ['schema' => $this->schemas->normalize(['title' => 'Imported Word form', 'description' => '', 'steps' => $steps, 'logic' => []]), 'warnings' => $warnings];
    }

    private function parseXlsx(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $warnings = [];
        $fields = [];
        $first = array_values($rows[1] ?? []);
        $headers = array_map(fn ($value) => Str::lower(trim((string) $value)), $first);

        if (in_array('label', $headers, true) && in_array('type', $headers, true)) {
            $map = array_flip($headers);
            foreach (array_slice($rows, 1) as $row) {
                $values = array_values($row);
                $label = trim((string) ($values[$map['label']] ?? ''));
                if ($label === '') {
                    continue;
                }
                $type = trim((string) ($values[$map['type']] ?? 'text'));
                $fields[] = $this->field($label, $type, trim((string) ($values[$map['options'] ?? -1] ?? '')));
            }
        } else {
            foreach ($first as $heading) {
                $heading = trim((string) $heading);
                if ($heading !== '') {
                    $fields[] = $this->fieldFromText($heading);
                }
            }
            $warnings[] = 'Detected plain header-row layout; each column became one field.';
        }

        return ['schema' => $this->schemas->normalize(['title' => 'Imported Excel form', 'description' => '', 'steps' => [[
            'id' => (string) Str::uuid(),
            'title' => 'Imported sheet',
            'fields' => $fields,
        ]], 'logic' => []]), 'warnings' => $warnings];
    }

    private function fieldFromText(string $text): array
    {
        $options = [];
        if (str_contains($text, '|')) {
            [$text, $optionText] = array_pad(explode('|', $text, 2), 2, '');
            $options = array_map('trim', explode(',', $optionText));
        }

        return $this->field(trim($text, " ?\t\n\r\0\x0B"), $this->inferType($text), implode(',', $options));
    }

    private function field(string $label, string $type, string $optionText = ''): array
    {
        $type = in_array($type, FormSchemaService::FIELD_TYPES, true) ? $type : $this->inferType($label);
        $options = $optionText !== '' ? array_map('trim', explode(',', $optionText)) : [];
        if (in_array($type, ['dropdown', 'radio', 'checkbox'], true) && $options === []) {
            $options = ['Yes', 'No'];
        }

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'label' => $label,
            'key' => Str::snake($label),
            'placeholder' => $label,
            'helpText' => '',
            'default' => null,
            'required' => str_contains(Str::lower($label), '*'),
            'options' => $options,
            'validation' => [],
        ];
    }

    private function inferType(string $text): string
    {
        $text = Str::lower($text);
        return match (true) {
            str_contains($text, 'email') => 'email',
            str_contains($text, 'phone') || str_contains($text, 'mobile') => 'phone',
            str_contains($text, 'date') => 'date',
            str_contains($text, 'resume') || str_contains($text, 'upload') || str_contains($text, 'file') => 'file',
            str_contains($text, 'rating') => 'rating',
            str_contains($text, 'choose') || str_contains($text, 'select') || str_contains($text, '|') => 'dropdown',
            str_contains($text, 'describe') || str_contains($text, 'address') || str_contains($text, 'history') => 'textarea',
            default => 'text',
        };
    }

    private function textFromElement(object $element): string
    {
        if (method_exists($element, 'getText')) {
            return (string) $element->getText();
        }
        if ($element instanceof TextRun) {
            return collect($element->getElements())->map(fn ($child) => method_exists($child, 'getText') ? $child->getText() : '')->implode(' ');
        }

        return '';
    }
}

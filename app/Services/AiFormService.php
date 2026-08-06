<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiFormService
{
    public function __construct(private readonly FormSchemaService $schemas)
    {
    }

    public function generate(string $prompt, ?array $existingSchema = null): array
    {
        $started = microtime(true);
        $model = config('services.openai.model', 'gpt-4o-mini');
        $raw = null;

        if (config('services.openai.key')) {
            try {
                $raw = $this->callOpenAi($prompt, $existingSchema, $model);
            } catch (Throwable) {
                $raw = null;
            }
        }

        $schema = $raw ? $this->decodeJson($raw) : null;
        if (! is_array($schema)) {
            $schema = $this->heuristicSchema($prompt, $existingSchema);
        }

        return [
            'schema' => $this->schemas->validate($schema),
            'model' => $raw ? $model : 'deterministic-fallback',
            'prompt_tokens' => str_word_count($prompt),
            'completion_tokens' => strlen(json_encode($schema)) / 4,
            'latency_ms' => (int) ((microtime(true) - $started) * 1000),
        ];
    }

    private function callOpenAi(string $prompt, ?array $existingSchema, string $model): string
    {
        $contract = 'Return only valid JSON for {title,description,steps:[{id,title,fields:[{id,type,label,key,placeholder,helpText,default,required,options,validation,visibility}]}],logic:[]}. Supported types: '.implode(', ', FormSchemaService::FIELD_TYPES).'.';
        $response = Http::withToken(config('services.openai.key'))->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a form schema compiler. '.$contract],
                ['role' => 'user', 'content' => json_encode(['instruction' => $prompt, 'existing_schema' => $existingSchema])],
            ],
        ])->throw()->json();

        return $response['choices'][0]['message']['content'] ?? '';
    }

    private function decodeJson(string $raw): ?array
    {
        $raw = trim($raw);
        if (str_starts_with($raw, '```')) {
            $raw = preg_replace('/^```json|^```|```$/m', '', $raw);
        }
        $first = strpos($raw, '{');
        $last = strrpos($raw, '}');
        if ($first !== false && $last !== false) {
            $raw = substr($raw, $first, $last - $first + 1);
        }

        return json_decode($raw, true);
    }

    private function heuristicSchema(string $prompt, ?array $existingSchema): array
    {
        $schema = $existingSchema ?: $this->schemas->defaultSchema(Str::headline(Str::limit($prompt, 48, '')));
        $fields = [];
        $text = Str::lower($prompt);

        $candidates = [
            ['name', 'text', 'Full name', true],
            ['email', 'email', 'Email address', true],
            ['phone', 'phone', 'Phone number', str_contains($text, 'phone')],
            ['education', 'textarea', 'Education history', str_contains($text, 'education')],
            ['skills', 'textarea', 'Skills', str_contains($text, 'skills')],
            ['resume', 'file', 'Resume upload', str_contains($text, 'resume') || str_contains($text, 'cv')],
            ['emergency_contact', 'text', 'Emergency contact', str_contains($text, 'emergency')],
            ['rating', 'rating', 'Overall rating', str_contains($text, 'rating')],
            ['start_date', 'date', 'Available start date', str_contains($text, 'date')],
        ];

        foreach ($candidates as [$key, $type, $label, $include]) {
            if (! $include) {
                continue;
            }
            $fields[] = [
                'id' => (string) Str::uuid(),
                'type' => $type,
                'label' => $label,
                'key' => $key,
                'placeholder' => $label,
                'helpText' => '',
                'default' => null,
                'required' => in_array($key, ['name', 'email'], true),
                'options' => [],
                'validation' => $type === 'file' ? ['fileTypes' => ['pdf', 'doc', 'docx'], 'maxFileSizeKb' => 5120] : [],
            ];
        }

        if (str_contains($text, 'hindi') || str_contains($text, 'translate')) {
            foreach ($schema['steps'] as &$step) {
                foreach ($step['fields'] as &$field) {
                    $field['label'] = match ($field['key']) {
                        'name' => 'पूरा नाम',
                        'email' => 'ईमेल पता',
                        'phone' => 'फोन नंबर',
                        default => $field['label'],
                    };
                }
            }
        }

        $schema['steps'][] = ['id' => (string) Str::uuid(), 'title' => 'Generated section', 'fields' => $fields];

        return $schema;
    }
}

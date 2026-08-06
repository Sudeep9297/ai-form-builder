<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FormService
{
    public function __construct(private readonly FormSchemaService $schemas)
    {
    }

    public function create(User $user, array $data): Form
    {
        $schema = $this->schemas->validate($data['schema'] ?? $this->schemas->defaultSchema($data['title'] ?? 'Untitled form'));

        $form = Form::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'title' => $data['title'] ?? $schema['title'],
            'slug' => $this->uniqueSlug($user->tenant_id, $data['title'] ?? $schema['title']),
            'public_token' => Str::random(40),
            'description' => $data['description'] ?? $schema['description'] ?? null,
            'schema' => $schema,
            'settings' => $data['settings'] ?? ['allow_multiple' => true, 'spam_honeypot' => true],
            'is_published' => (bool) ($data['is_published'] ?? false),
            'version' => 1,
            'published_at' => ($data['is_published'] ?? false) ? now() : null,
        ]);

        $this->snapshot($form, $user, 'Initial version');

        return $form;
    }

    public function update(Form $form, User $user, array $data, string $summary = 'Manual edit'): Form
    {
        $schema = $this->schemas->validate($data['schema'] ?? $form->schema);
        $form->fill([
            'title' => $data['title'] ?? $schema['title'] ?? $form->title,
            'description' => $data['description'] ?? $schema['description'] ?? $form->description,
            'schema' => $schema,
            'settings' => $data['settings'] ?? $form->settings,
            'is_published' => (bool) ($data['is_published'] ?? $form->is_published),
        ]);
        $form->version++;
        $form->published_at = $form->is_published ? ($form->published_at ?? now()) : null;
        $form->save();

        Cache::forget($this->compiledCacheKey($form));
        $this->snapshot($form, $user, $summary);

        return $form;
    }

    public function rollback(Form $form, FormVersion $version, User $user): Form
    {
        return $this->update($form, $user, [
            'schema' => $version->schema,
            'settings' => $version->settings,
            'is_published' => $form->is_published,
        ], 'Rollback to v'.$version->version);
    }

    public function compiledRules(Form $form): array
    {
        return Cache::remember($this->compiledCacheKey($form), now()->addMinutes(30), fn () => app(FormSchemaService::class)->rulesFor($form->schema));
    }

    private function snapshot(Form $form, User $user, string $summary): void
    {
        FormVersion::create([
            'form_id' => $form->id,
            'user_id' => $user->id,
            'version' => $form->version,
            'change_summary' => $summary,
            'schema' => $form->schema,
            'settings' => $form->settings,
        ]);
    }

    private function uniqueSlug(int $tenantId, string $title): string
    {
        $base = Str::slug($title) ?: 'form';
        $slug = $base;
        $suffix = 2;

        while (Form::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function compiledCacheKey(Form $form): string
    {
        return 'forms:'.$form->id.':rules:v'.$form->version;
    }
}

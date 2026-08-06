<?php

use App\Jobs\GenerateFormWithAi;
use App\Jobs\ProcessImportBatch;
use App\Models\AiGeneration;
use App\Models\ImportBatch;
use App\Models\Submission;
use App\Models\User;
use App\Services\FormService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

function assignmentSchema(): array
{
    return [
        'title' => 'Contact Form',
        'description' => 'A schema-backed contact form.',
        'steps' => [[
            'id' => 'contact',
            'title' => 'Contact',
            'fields' => [
                ['id' => 'name', 'type' => 'text', 'label' => 'Full name', 'key' => 'name', 'placeholder' => 'Jane Doe', 'helpText' => '', 'default' => null, 'required' => true, 'options' => [], 'validation' => ['maxLength' => 120]],
                ['id' => 'email', 'type' => 'email', 'label' => 'Email', 'key' => 'email', 'placeholder' => 'jane@example.com', 'helpText' => '', 'default' => null, 'required' => true, 'options' => [], 'validation' => ['email' => true]],
            ],
        ]],
        'logic' => [],
    ];
}

it('creates schema-backed forms with version snapshots', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('forms.store'), [
            'title' => 'Contact Form',
            'description' => 'A schema-backed contact form.',
            'schema' => assignmentSchema(),
            'is_published' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('forms', ['title' => 'Contact Form', 'version' => 1, 'is_published' => true]);
    $this->assertDatabaseHas('form_versions', ['version' => 1, 'change_summary' => 'Initial version']);
});

it('validates public submissions from the saved schema', function () {
    $user = User::factory()->create();
    $form = app(FormService::class)->create($user, [
        'title' => 'Contact Form',
        'schema' => assignmentSchema(),
        'is_published' => true,
    ]);

    $this->post(route('public.forms.submit', $form->public_token), [
        'answers' => ['name' => 'Sudeep', 'email' => 'not-an-email'],
    ])->assertSessionHasErrors('email');

    $this->post(route('public.forms.submit', $form->public_token), [
        'answers' => ['name' => 'Sudeep', 'email' => 'sudeep@example.com'],
    ])->assertSessionHasNoErrors();

    expect(Submission::where('form_id', $form->id)->count())->toBe(1);
});

it('queues AI jobs and stores schema-valid results', function () {
    Queue::fake();
    $user = User::factory()->create();
    app(FormService::class)->create($user, [
        'title' => 'Seed Form',
        'schema' => assignmentSchema(),
    ]);

    $this->actingAs($user)
        ->post(route('ai-generations.store'), [
            'mode' => 'create',
            'prompt' => 'internship application with education history, skills and resume upload',
        ])
        ->assertRedirect();

    Queue::assertPushed(GenerateFormWithAi::class);

    $generation = AiGeneration::first();
    Queue::pushed(GenerateFormWithAi::class)->first()->handle(app(\App\Services\AiFormService::class));

    $generation->refresh();
    expect($generation->status)->toBe('completed');
    expect($generation->result_schema['steps'])->not->toBeEmpty();
});

it('parses spreadsheet imports into a mapping-ready schema', function () {
    Queue::fake();
    $user = User::factory()->create();
    app(FormService::class)->create($user, ['title' => 'Seed Form', 'schema' => assignmentSchema()]);

    $file = new UploadedFile(
        base_path('samples/structured-import.xlsx'),
        'structured-import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $this->actingAs($user)
        ->post(route('imports.store'), ['source' => $file])
        ->assertRedirect();

    Queue::assertPushed(ProcessImportBatch::class);

    $batch = ImportBatch::first();
    Queue::pushed(ProcessImportBatch::class)->first()->handle(app(\App\Services\ImportParserService::class));

    $batch->refresh();
    expect($batch->status)->toBe('ready_for_mapping');
    expect($batch->detected_schema['steps'][0]['fields'])->toHaveCount(4);
});

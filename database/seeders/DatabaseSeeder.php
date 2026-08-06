<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FormService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'demo'], ['name' => 'Demo Workspace']);

        $user = User::firstOrCreate([
            'email' => 'demo@example.com',
        ], [
            'tenant_id' => $tenant->id,
            'name' => 'Demo Admin',
            'password' => Hash::make('password'),
        ]);
        $user->update(['tenant_id' => $tenant->id]);

        $schema = [
            'title' => 'Internship Application',
            'description' => 'Collect candidate details, skills and resume uploads.',
            'steps' => [[
                'id' => 'step-profile',
                'title' => 'Profile',
                'fields' => [
                    ['id' => 'name', 'type' => 'text', 'label' => 'Full name', 'key' => 'name', 'placeholder' => 'Jane Doe', 'helpText' => '', 'default' => null, 'required' => true, 'options' => [], 'validation' => ['maxLength' => 120]],
                    ['id' => 'email', 'type' => 'email', 'label' => 'Email address', 'key' => 'email', 'placeholder' => 'jane@example.com', 'helpText' => '', 'default' => null, 'required' => true, 'options' => [], 'validation' => ['email' => true]],
                    ['id' => 'phone', 'type' => 'phone', 'label' => 'Phone number', 'key' => 'phone', 'placeholder' => '+1 555 0100', 'helpText' => '', 'default' => null, 'required' => true, 'options' => [], 'validation' => ['minLength' => 8, 'maxLength' => 24]],
                ],
            ], [
                'id' => 'step-application',
                'title' => 'Application',
                'fields' => [
                    ['id' => 'education', 'type' => 'textarea', 'label' => 'Education history', 'key' => 'education', 'placeholder' => 'Degree, institution and year', 'helpText' => '', 'default' => null, 'required' => true, 'options' => [], 'validation' => ['minLength' => 20]],
                    ['id' => 'skills', 'type' => 'checkbox', 'label' => 'Skills', 'key' => 'skills', 'placeholder' => '', 'helpText' => '', 'default' => [], 'required' => true, 'options' => [['label' => 'PHP', 'value' => 'php'], ['label' => 'React', 'value' => 'react'], ['label' => 'SQL', 'value' => 'sql']], 'validation' => []],
                    ['id' => 'resume', 'type' => 'file', 'label' => 'Resume upload', 'key' => 'resume', 'placeholder' => '', 'helpText' => 'PDF, DOC or DOCX up to 5MB.', 'default' => null, 'required' => false, 'options' => [], 'validation' => ['fileTypes' => ['pdf', 'doc', 'docx'], 'maxFileSizeKb' => 5120]],
                ],
            ]],
            'logic' => [],
        ];

        if (! Form::where('tenant_id', $tenant->id)->where('slug', 'internship-application')->exists()) {
            app(FormService::class)->create($user, [
                'title' => 'Internship Application',
                'description' => $schema['description'],
                'schema' => $schema,
                'is_published' => true,
            ]);
        }

        Template::firstOrCreate(['name' => 'Customer Feedback', 'category' => 'feedback'], [
            'tenant_id' => null,
            'description' => 'NPS-style feedback form with rating and comments.',
            'schema' => [
                'title' => 'Customer Feedback',
                'description' => 'Understand customer satisfaction.',
                'steps' => [[
                    'id' => 'feedback',
                    'title' => 'Feedback',
                    'fields' => [
                        ['id' => 'rating', 'type' => 'rating', 'label' => 'How would you rate us?', 'key' => 'rating', 'placeholder' => '', 'helpText' => '', 'default' => null, 'required' => true, 'options' => [], 'validation' => ['min' => 1, 'max' => 5]],
                        ['id' => 'comments', 'type' => 'textarea', 'label' => 'What can we improve?', 'key' => 'comments', 'placeholder' => 'Share details', 'helpText' => '', 'default' => null, 'required' => false, 'options' => [], 'validation' => []],
                    ],
                ]],
                'logic' => [],
            ],
            'is_system' => true,
        ]);
    }
}

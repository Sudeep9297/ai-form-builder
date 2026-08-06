<?php

namespace App\Listeners;

use App\Events\FormSubmitted;
use App\Jobs\DispatchSubmissionWebhook;
use App\Models\WebhookEndpoint;

class QueueSubmissionWebhooks
{
    public function handle(FormSubmitted $event): void
    {
        WebhookEndpoint::query()
            ->where('tenant_id', $event->submission->tenant_id)
            ->where('is_active', true)
            ->where(function ($query) use ($event) {
                $query->whereNull('form_id')->orWhere('form_id', $event->submission->form_id);
            })
            ->cursor()
            ->each(fn (WebhookEndpoint $endpoint) => DispatchSubmissionWebhook::dispatch($endpoint, $event->submission));
    }
}

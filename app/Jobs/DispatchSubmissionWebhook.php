<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Models\WebhookEndpoint;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class DispatchSubmissionWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public WebhookEndpoint $endpoint, public Submission $submission)
    {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $payload = [
            'event' => 'form.submitted',
            'form_id' => $this->submission->form_id,
            'submission_id' => $this->submission->id,
            'submitted_at' => $this->submission->created_at->toIso8601String(),
            'answers' => $this->submission->payload,
        ];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $this->endpoint->secret);

        try {
            Http::timeout(10)->withHeaders([
                'X-FormBuilder-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint->url, $payload)->throw();

            $this->endpoint->update(['last_delivered_at' => now(), 'failure_count' => 0]);
        } catch (Throwable $exception) {
            $this->endpoint->increment('failure_count');
            throw $exception;
        }
    }
}

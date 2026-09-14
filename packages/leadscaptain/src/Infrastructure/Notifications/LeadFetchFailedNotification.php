<?php

declare(strict_types=1);

namespace Leadscaptain\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadFetchFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $page,
        public readonly string $errorMessage,
        public readonly ?string $batchId = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Leadscaptain Batch Fetch Failure')
            ->line("Fetching leads for page {$this->page} failed after retries.")
            ->line('Batch ID: '.($this->batchId ?? 'N/A'))
            ->line("Error: {$this->errorMessage}");
    }
}

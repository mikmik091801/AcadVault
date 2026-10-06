<?php

namespace App\Notifications;

use App\Models\AcademicRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradePosted extends Notification
{
    use Queueable;

    public function __construct(
        public AcademicRecord $record,
        public bool $posted = true,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->record->course?->code ?? 'your course';

        return (new MailMessage)
            ->subject(($this->posted ? 'Grade posted' : 'Grade updated')." — {$course}")
            ->greeting("Hello {$notifiable->name},")
            ->line(($this->posted
                ? "A grade has been posted"
                : "Your grade has been updated")." for **{$course}**.")
            ->line('Log in to AcadVault to view the details.')
            ->action('View your records', route('records.index'));
    }
}
